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

namespace TYPO3\CMS\Assist\Service;

use TYPO3\CMS\Assist\Domain\Authorization;
use TYPO3\CMS\Assist\Domain\Availability;
use TYPO3\CMS\Assist\Domain\Platform;
use TYPO3\CMS\Core\Site\SiteFinder;

/**
 * Resolves platforms from site configuration (`assist` section in `sites/my-site/config.yaml`).
 */
final readonly class PlatformResolver
{
    public function __construct(
        private PackageService $packageService,
        private SiteFinder $siteFinder,
    ) {}

    /**
     * Resolves the platforms of the site configuration that is declared to be default.
     *
     * @return list<Platform>
     */
    public function getDefaultPlatforms(): array
    {
        foreach ($this->siteFinder->getAllSites(true) as $site) {
            $configuration = $site->getConfiguration();
            if (!empty($configuration['assistDefault'])) {
                return $this->getCurrentPlatforms($site->getIdentifier());
            }
        }
        return [];
    }

    /**
     * Resolves the platforms of a particular site configuration.
     *
     * @param string $siteIdentifier
     * @return list<Platform>
     */
    public function getCurrentPlatforms(string $siteIdentifier): array
    {
        $site = $this->siteFinder->getSiteByIdentifier($siteIdentifier);
        $configuration = $site->getConfiguration();
        if (empty($configuration['assistPlatforms'])) {
            return [];
        }
        return $this->buildPlatforms($configuration['assistPlatforms']);
    }

    /**
     * @return list<Platform>
     */
    private function buildPlatforms(array $platformsConfiguration): array
    {
        return array_map($this->buildPlatform(...), $platformsConfiguration);
    }

    private function buildPlatform(array $data): Platform
    {
        $options = [];
        if (($data['baseUrl'] ?? '') !== '') {
            $options['baseUrl'] = $data['baseUrl'];
        }

        $authorization = null;
        if (($data['authorizationType'] ?? 'none') !== 'none' && ($data['authorizationToken'] ?? '') !== '') {
            $authorization = new Authorization($data['authorizationType'], $data['authorizationToken']);
        }

        if (!$this->packageService->isPackageInstalled($data['package'])) {
            $availability = Availability::unavailable;
        } elseif ($data['enabled'] ?? false) {
            $availability = Availability::enabled;
        } else {
            $availability = Availability::disabled;
        }

        return new Platform(
            availability: $availability,
            name: $data['name'],
            package: $data['package'],
            options: $options,
            authorization: $authorization,
        );
    }
}
