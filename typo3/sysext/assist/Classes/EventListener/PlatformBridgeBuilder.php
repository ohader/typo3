<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Assist\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\AI\Platform\Bridge\Generic\CompletionsModel;
use Symfony\AI\Platform\Capability;
use Symfony\AI\Platform\ModelCatalog\ModelCatalogInterface;
use TYPO3\CMS\Assist\AI\Platform\ModelCatalog;
use TYPO3\CMS\Assist\AI\Platform\PlatformDetails;
use TYPO3\CMS\Assist\Domain\Enum\AuthenticationType;
use TYPO3\CMS\Assist\Domain\Enum\Availability;
use TYPO3\CMS\Assist\Event\BeforeBuildPlatformBridgeEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Http\Uri;

/**
 * Resolves live models by calling the platform's models API endpoint.
 * Discovered models are injected as additionalModels into the vendor's own catalog.
 */
#[AsEventListener('typo3/cms-assist/build-platform-bridge')]
final class PlatformBridgeBuilder
{
    public function __construct(
        private PlatformDetails $platformDetails,
        private RequestFactory $requestFactory,
        private LoggerInterface $logger,
    ) {}

    public function __invoke(BeforeBuildPlatformBridgeEvent $event): void
    {
        if ($event->platform->availability !== Availability::enabled) {
            return;
        }

        $package = $event->platform->package;
        $modelEndpoint = $this->platformDetails->getModelsEndpoint($package);
        if ($modelEndpoint === null) {
            return;
        }

        $baseUrl = $this->resolveBaseUrl($event);
        if ($baseUrl === null) {
            return;
        }

        $uri = (new Uri($baseUrl))->withPath($modelEndpoint);
        $headers = $this->buildHeaders($package, $event);

        try {
            $response = $this->requestFactory->request(
                (string)$uri,
                'GET',
                ['headers' => $headers]
            );
            $data = json_decode($response->getBody()->getContents(), true);
        } catch (\Throwable $e) {
            $this->logger->warning('Failed to fetch models from {uri}: {message}', [
                'uri' => (string)$uri,
                'message' => $e->getMessage(),
            ]);
            return;
        }

        $models = $this->buildModels($data, $package);
        $modelCatalogParam = $this->getModelCatalogParam($event->reflector->getPlatformFactoryParamTypes());

        // replace ModelCatalog completely (if the parameter is present)
        if ($modelCatalogParam !== null) {
            $modelCatalog = new ModelCatalog($models);
            $event->setOptions([
                ...$event->getOptions(),
                'platformFactory' => ['modelCatalog' => $modelCatalog],
                'modelCatalog' => $modelCatalog,
            ]);
            // otherwise, at least append to the existing catalog
        } else {
            $event->setOptions([
                ...$event->getOptions(),
                'modelCatalog' => ['additionalModels' => $models],
            ]);
        }
    }

    private function getModelCatalogParam(array $paramTypes): ?string
    {
        foreach ($paramTypes as $param => $type) {
            if (is_a($type, ModelCatalogInterface::class, true)) {
                return $param;
            }
        }
        return null;
    }

    private function resolveBaseUrl(BeforeBuildPlatformBridgeEvent $event): ?string
    {
        $paramName = $this->platformDetails->getPlatformEndpointParam($event->platform->package);
        if ($paramName === null) {
            return null;
        }

        $options = $event->getOptions()['platformFactory'] ?? [];
        $baseUrl = $options[$paramName] ?? null;

        if ($baseUrl === null || $baseUrl === '') {
            $defaults = $event->reflector->getPlatformFactoryOptions();
            $baseUrl = $defaults[$paramName] ?? null;
        }

        return $baseUrl !== null && $baseUrl !== '' ? $baseUrl : null;
    }

    /**
     * @return array<string, string>
     */
    private function buildHeaders(string $package, BeforeBuildPlatformBridgeEvent $event): array
    {
        $headers = ['Content-Type' => 'application/json'];

        $authType = $this->platformDetails->getAuthenticationType($package);
        $authParam = $this->platformDetails->getAuthenticationParam($package);
        $apiKey = $authParam !== null ? ($event->getOptions()['platformFactory'][$authParam] ?? null) : null;

        if ($authType !== null && $apiKey !== null && $apiKey !== '') {
            if ($authType === AuthenticationType::bearer) {
                $headers['Authorization'] = 'Bearer ' . $apiKey;
            } elseif ($authType === AuthenticationType::header) {
                $headerName = $this->platformDetails->getAuthenticationName($package);
                if ($headerName !== null) {
                    $headers[$headerName] = $apiKey;
                }
            }
        }

        return [...$headers, ...$this->platformDetails->getAdditionalHeaders($package)];
    }

    private function buildModels(
        array $responseData,
        string $package,
    ): array {

        $idKey = $this->platformDetails->getModelsMappings($package)['response']['idKey'] ?? '';
        $listKey = $this->platformDetails->getModelsMappings($package)['response']['listKey'] ?? '';
        $typeKey = $this->platformDetails->getModelsMappings($package)['response']['typeKey'] ?? '';
        $typeModelMapping = $this->platformDetails->getModelsMappings($package)['typeModel'] ?? [];
        $typeCapabilityMapping = $this->platformDetails->getModelsMappings($package)['typeCapability'] ?? [];
        $modelCapabilityMapping = $this->platformDetails->getModelsMappings($package)['modelCapability'] ?? [];

        $resolvedModels = [];

        foreach ($responseData[$listKey] ?? [] as $model) {
            $identifier = $model[$idKey] ?? null;
            if ($identifier === null) {
                continue;
            }

            if ($typeModelMapping !== []) {
                $type = $model[$typeKey] ?? null;
                if ($type === null || !isset($typeModelMapping[$type])) {
                    continue;
                }

                $class = $typeModelMapping[$type];

                $capabilities = [];
                $capabilities = $this->mergeCapabilities(
                    $capabilities,
                    $typeCapabilityMapping[$type] ?? null
                );
                foreach ($modelCapabilityMapping as $key => $capability) {
                    if (!empty($model['capabilities'][$key])) {
                        $capabilities[] = $capability;
                    }
                }
            } else {
                // default assumptions
                $class = CompletionsModel::class;
                $capabilities = [Capability::INPUT_MESSAGES, Capability::OUTPUT_TEXT, Capability::OUTPUT_STREAMING];
            }

            $resolvedModels[$identifier] = [
                'class' => $class,
                'capabilities' => $capabilities,
            ];
        }

        return $resolvedModels;
    }

    private function mergeCapabilities(array $target, mixed $source): array
    {
        if (is_array($source)) {
            $target = array_merge($target, $source);
        } elseif ($source instanceof \UnitEnum) {
            $target[] = $source;
        }
        return $target;
    }
}
