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

namespace TYPO3\CMS\Assist\Controller\Ajax;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\AI\Platform\Capability;
use TYPO3\CMS\Assist\AI\Platform\PlatformConnector;
use TYPO3\CMS\Assist\AI\Platform\PlatformModel;
use TYPO3\CMS\Assist\AI\Platform\PlatformResolver;
use TYPO3\CMS\Assist\Domain\Enum\Availability;
use TYPO3\CMS\Assist\Service\AssistantRegistry;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Core\Configuration\SiteWriter;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Site\SiteFinder;

/**
 * @internal This class is a specific TYPO3 Backend controller implementation and is not part of the Public TYPO3 API.
 * @todo enforce permission checks
 */
#[AsController]
class PlatformAjaxController
{
    public function __construct(
        private readonly PlatformResolver $platformResolver,
        private readonly PlatformConnector $platformConnector,
        private readonly AssistantRegistry $assistantRegistry,
        private readonly SiteFinder $siteFinder,
        private readonly SiteWriter $siteWriter,
    ) {}

    public function checkConnection(ServerRequestInterface $request): ResponseInterface
    {
        $body = $request->getParsedBody();
        $siteIdentifier = (string)($body['siteIdentifier'] ?? '');
        $platformIdentifier = (string)($body['platformIdentifier'] ?? '');

        try {
            $platform = $this->platformResolver->getSitePlatform($siteIdentifier, $platformIdentifier);
            if ($platform === null) {
                return new JsonResponse(['success' => false, 'error' => 'Platform not found.'], 404);
            }
            $result = $this->platformConnector->checkConnection($platform);
            return new JsonResponse($result);
        } catch (\Throwable $e) {
            return new JsonResponse(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function getModels(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();
        $siteIdentifier = (string)($params['siteIdentifier'] ?? '');
        $platformIndex = (int)($params['platformIndex'] ?? 0);

        try {
            $platforms = $this->platformResolver->getSitePlatforms($siteIdentifier);
            $platform = $platforms[$platformIndex] ?? null;
            if ($platform === null) {
                return new JsonResponse(['models' => []], 404);
            }

            $bridge = $this->platformConnector->buildBridge($platform, false);
            $catalog = $bridge->getModelCatalog();
            $catalogModels = $catalog->getModels();
            $enabledModels = $platform->models;

            $models = [];
            foreach ($catalogModels as $name => $meta) {
                $capabilities = array_map(
                    static fn(Capability $cap): string => $cap->value,
                    $meta['capabilities'] ?? [],
                );
                $models[] = [
                    'name' => $name,
                    'capabilities' => $capabilities,
                    'enabled' => in_array($name, $enabledModels, true),
                ];
            }

            return new JsonResponse(['models' => $models]);
        } catch (\Throwable $e) {
            return new JsonResponse(['models' => [], 'error' => $e->getMessage()]);
        }
    }

    public function updateModels(ServerRequestInterface $request): ResponseInterface
    {
        $body = $request->getParsedBody();
        $siteIdentifier = (string)($body['siteIdentifier'] ?? '');
        $platformIndex = (int)($body['platformIndex'] ?? 0);
        $models = (array)($body['models'] ?? []);

        try {
            $site = $this->siteFinder->getSiteByIdentifier($siteIdentifier);
            $configuration = $site->getConfiguration();
            $assistPlatforms = $configuration['assistPlatforms'] ?? [];

            if (!isset($assistPlatforms[$platformIndex])) {
                return new JsonResponse(['success' => false, 'error' => 'Platform not found.'], 404);
            }

            $assistPlatforms[$platformIndex]['models'] = array_values($models);
            $configuration['assistPlatforms'] = $assistPlatforms;

            $this->siteWriter->write($siteIdentifier, $configuration);

            return new JsonResponse(['success' => true]);
        } catch (\Throwable $e) {
            return new JsonResponse(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function getMatchingModels(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();
        $siteIdentifier = (string)($params['siteIdentifier'] ?? '');
        $assistantIdentifier = (string)($params['assistantIdentifier'] ?? '');

        try {
            if (!$this->assistantRegistry->hasAssistant($assistantIdentifier)) {
                return new JsonResponse(['models' => []], 404);
            }
            $assistant = $this->assistantRegistry->getAssistant($assistantIdentifier);
            $platforms = $this->platformResolver->getSitePlatforms($siteIdentifier);
            $models = [];

            foreach ($platforms as $platform) {
                if ($platform->availability !== Availability::enabled) {
                    continue;
                }
                $bridge = $this->platformConnector->buildBridge($platform, false);
                $catalog = $bridge->getModelCatalog();
                $catalogModels = $catalog->getModels();
                $enabledModels = $platform->models;

                foreach ($catalogModels as $name => $meta) {
                    if ($enabledModels !== [] && !in_array($name, $enabledModels, true)) {
                        continue;
                    }
                    $modelCapabilities = $meta['capabilities'] ?? [];
                    if ($this->modelSatisfiesCapabilities($assistant->capabilities, $modelCapabilities)) {
                        $models[] = [
                            'identifier' => (string)new PlatformModel($platform->package, $name),
                            'platform' => $platform->name,
                            'model' => $name,
                        ];
                    }
                }
            }

            return new JsonResponse(['models' => $models]);
        } catch (\Throwable $e) {
            return new JsonResponse(['models' => [], 'error' => $e->getMessage()]);
        }
    }

    public function updateAssistantModel(ServerRequestInterface $request): ResponseInterface
    {
        $body = $request->getParsedBody();
        $siteIdentifier = (string)($body['siteIdentifier'] ?? '');
        $assistantIdentifier = (string)($body['assistantIdentifier'] ?? '');
        $model = (string)($body['model'] ?? '');

        try {
            if ($model !== '') {
                // validate `model@platform` syntax
                PlatformModel::fromString($model);
            }

            $site = $this->siteFinder->getSiteByIdentifier($siteIdentifier);
            $configuration = $site->getConfiguration();
            $assistAssistants = $configuration['assistAssistants'] ?? [];

            if ($model === '') {
                unset($assistAssistants[$assistantIdentifier]);
            } else {
                $assistAssistants[$assistantIdentifier] = ['model' => $model];
            }

            $configuration['assistAssistants'] = $assistAssistants;
            $this->siteWriter->write($siteIdentifier, $configuration);

            return new JsonResponse(['success' => true]);
        } catch (\Throwable $e) {
            return new JsonResponse(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * @param list<Capability> $requiredCapabilities
     * @param list<Capability> $modelCapabilities
     */
    private function modelSatisfiesCapabilities(array $requiredCapabilities, array $modelCapabilities): bool
    {
        foreach ($requiredCapabilities as $required) {
            if (!in_array($required, $modelCapabilities, true)) {
                return false;
            }
        }
        return true;
    }
}
