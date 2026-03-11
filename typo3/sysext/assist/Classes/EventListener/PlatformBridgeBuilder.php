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
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Assist\AI\Http\RecordingHttpClient;
use TYPO3\CMS\Assist\AI\Platform\ModelCatalog;
use TYPO3\CMS\Assist\AI\Platform\PlatformDetails;
use TYPO3\CMS\Assist\Domain\Enum\AuthenticationType;
use TYPO3\CMS\Assist\Domain\Enum\Availability;
use TYPO3\CMS\Assist\Event\BeforeBuildPlatformBridgeEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Http\Uri;

/**
 * Resolves live models by calling the platform's models API endpoint.
 * Discovered models are injected as additionalModels into the vendor's own catalog.
 */
#[AsEventListener('typo3/cms-assist/build-platform-bridge')]
final readonly class PlatformBridgeBuilder
{
    public function __construct(
        private PlatformDetails $platformDetails,
        private RequestFactory $requestFactory,
        private LoggerInterface $logger,
        #[Autowire(service: 'cache.hash')]
        private FrontendInterface $cache,
        private ExtensionConfiguration $extensionConfiguration,
    ) {}

    public function __invoke(BeforeBuildPlatformBridgeEvent $event): void
    {
        if ($this->isDebugHttpTrafficEnabled()) {
            $options = $event->getOptions();
            $options['platformFactory']['httpClient'] = new RecordingHttpClient($this->logger);
            $event->setOptions($options);
        }

        if ($event->platform->availability !== Availability::enabled) {
            return;
        }

        $package = $event->platform->package;
        $listEndpoint = $this->platformDetails->getModelsListEndpoint($package);
        if ($listEndpoint === null) {
            return;
        }

        $baseUrl = $this->resolveBaseUrl($event);
        if ($baseUrl === null) {
            return;
        }

        $apiKey = $this->resolveApiKey($package, $event);
        $cacheLifetime = $this->getCacheLifetime();
        $cacheIdentifier = $this->buildCacheIdentifier($package, $baseUrl, $apiKey);

        $models = false;
        if ($cacheLifetime > 0) {
            $models = $this->cache->get($cacheIdentifier);
        }

        if ($models === false) {
            $listPath = $listEndpoint['path'] ?? '';
            $listMethod = $listEndpoint['http']['method'] ?? 'GET';
            $uri = (new Uri($baseUrl))->withPath($listPath);
            $headers = $this->buildHeaders($package, $event);

            try {
                $response = $this->requestFactory->request(
                    (string)$uri,
                    $listMethod,
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

            $catalogClass = $event->reflector->getModelCatalogClassName();
            $staticModels = (new $catalogClass())->getModels();
            $models = $this->buildModels($data, $package, $staticModels);

            $detailConfig = $this->platformDetails->getModelsDetailEndpoint($package);
            if ($detailConfig !== null) {
                $mappings = $this->platformDetails->getModelsMappings($package) ?? [];
                $models = $this->enrichModelsWithDetails($models, $baseUrl, $headers, $detailConfig, $mappings);
            }

            if ($cacheLifetime > 0) {
                $this->cache->set($cacheIdentifier, $models, ['assist'], $cacheLifetime);
            }
        }

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
        $event->markLiveResolved();
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
        $baseUrl = $this->platformDetails->getBaseUrl($event->platform->package);
        if (!empty($baseUrl)) {
            return $baseUrl;
        }

        $paramName = $this->platformDetails->getPlatformEndpointParam($event->platform->package);
        if ($paramName !== null) {
            $defaults = $event->reflector->getPlatformFactoryOptions();
            $options = $event->getOptions()['platformFactory'] ?? [];
            $baseUrl = $options[$paramName] ?? $defaults[$paramName] ?? null;
        }

        return !empty($baseUrl) ? $baseUrl : null;
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
        array $staticModels = [],
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
                // use live model mappings if available
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
            } elseif (isset($staticModels[$identifier])) {
                // fallback to static models (if available)
                $class = $staticModels[$identifier]['class'];
                $capabilities = $staticModels[$identifier]['capabilities'];
            } else {
                // skip model since no capabilities can be resolved
                // @todo this is a problem e.g. for OpenAI, due to the lack of capabilities in the API response
                // (when OpenAI releases new models, that are not part of the Symfony AI model catalog yet,
                // these models won't be shown - OpenAI does not provide an API to resolve model capabilities)
                continue;
            }

            $resolvedModels[$identifier] = [
                'class' => $class,
                'capabilities' => $capabilities,
            ];
        }

        return $resolvedModels;
    }

    /**
     * @param array<string, array{class: class-string, capabilities: list<Capability>}> $models
     * @param array<string, string> $headers
     * @return array<string, array{class: class-string, capabilities: list<Capability>}>
     */
    private function enrichModelsWithDetails(array $models, string $baseUrl, array $headers, array $detailConfig, array $mappings): array
    {
        $path = $detailConfig['path'] ?? '';
        $method = $detailConfig['http']['method'] ?? 'POST';
        $bodyKey = $detailConfig['http']['bodyKey'] ?? null;
        $responseCapabilitiesKey = $mappings['response']['capabilitiesKey'] ?? 'capabilities';
        $defaultClass = $mappings['defaultModel'] ?? CompletionsModel::class;
        $capabilityMap = $mappings['capabilityMap'] ?? [];

        $uri = (string)(new Uri($baseUrl))->withPath($path);

        foreach ($models as $modelId => $modelEntry) {
            try {
                $requestOptions = ['headers' => $headers];
                if ($bodyKey !== null) {
                    $requestOptions['body'] = json_encode([$bodyKey => $modelId]);
                }
                $response = $this->requestFactory->request($uri, $method, $requestOptions);
                $data = json_decode($response->getBody()->getContents(), true);
            } catch (\Throwable $e) {
                $this->logger->warning('Failed to fetch model details for {model} from {uri}: {message}', [
                    'model' => $modelId,
                    'uri' => $uri,
                    'message' => $e->getMessage(),
                ]);
                continue;
            }

            $rawCapabilities = $data[$responseCapabilitiesKey] ?? [];
            if (!is_array($rawCapabilities) || $rawCapabilities === []) {
                continue;
            }

            $capabilities = [];
            foreach ($rawCapabilities as $capabilityName) {
                if (isset($capabilityMap[$capabilityName])) {
                    $mapped = $capabilityMap[$capabilityName];
                    $capabilities = $this->mergeCapabilities($capabilities, $mapped);
                }
            }

            if ($capabilities !== []) {
                $models[$modelId] = [
                    'class' => $defaultClass,
                    'capabilities' => $capabilities,
                ];
            }
        }

        return $models;
    }

    private function resolveApiKey(string $package, BeforeBuildPlatformBridgeEvent $event): ?string
    {
        $authParam = $this->platformDetails->getAuthenticationParam($package);
        return $authParam !== null ? ($event->getOptions()['platformFactory'][$authParam] ?? null) : null;
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

    private function buildCacheIdentifier(string $package, string $baseUrl, ?string $apiKey): string
    {
        $payload = [
            'package' => $package,
            'baseUrl' => $baseUrl,
            'apiKey' => $apiKey,
        ];
        return 'assist-remote-models-' . hash('xxh128', json_encode($payload));
    }

    private function getCacheLifetime(): int
    {
        try {
            return (int)$this->extensionConfiguration->get('assist', 'modelCacheLifetime');
        } catch (\Exception) {
            return 10800;
        }
    }

    private function isDebugHttpTrafficEnabled(): bool
    {
        try {
            return (bool)$this->extensionConfiguration->get('assist', 'debugHttpTraffic');
        } catch (\Exception) {
            return false;
        }
    }
}
