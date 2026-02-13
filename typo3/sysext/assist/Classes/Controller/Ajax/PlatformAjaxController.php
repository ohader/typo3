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
use TYPO3\CMS\Assist\Service\PackageService;
use TYPO3\CMS\Assist\Service\PlatformConnector;
use TYPO3\CMS\Assist\Service\PlatformResolver;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Core\Configuration\SiteWriter;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Site\SiteFinder;

/**
 * @internal This class is a specific TYPO3 Backend controller implementation and is not part of the Public TYPO3 API.
 */
#[AsController]
class PlatformAjaxController
{
    public function __construct(
        private readonly PlatformResolver $platformResolver,
        private readonly PlatformConnector $platformConnector,
        private readonly PackageService $packageService,
        private readonly SiteFinder $siteFinder,
        private readonly SiteWriter $siteWriter,
    ) {}

    public function checkConnection(ServerRequestInterface $request): ResponseInterface
    {
        $body = $request->getParsedBody();
        $siteIdentifier = (string)($body['siteIdentifier'] ?? '');
        $platformIndex = (int)($body['platformIndex'] ?? 0);

        try {
            $platforms = $this->platformResolver->getCurrentPlatforms($siteIdentifier);
            $platform = $platforms[$platformIndex] ?? null;
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
            $platforms = $this->platformResolver->getCurrentPlatforms($siteIdentifier);
            $platform = $platforms[$platformIndex] ?? null;
            if ($platform === null) {
                return new JsonResponse(['models' => []], 404);
            }

            $bridge = $this->packageService->buildBridge($platform);
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
}
