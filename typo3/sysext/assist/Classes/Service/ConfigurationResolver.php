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

use TYPO3\CMS\Assist\AI\Platform\PlatformModel;
use TYPO3\CMS\Assist\Domain\Enum\Availability;
use TYPO3\CMS\Assist\Domain\Model\Platform;
use TYPO3\CMS\Core\Site\SiteFinder;

/**
 * Resolves platforms from site configuration (`assist` section in `sites/my-site/config.yaml`).
 */
final readonly class ConfigurationResolver
{
    private const PACKAGE_STRUCTURAL_KEYS = ['enabled', 'name', 'package', 'models'];

    public function __construct(
        private PackageService $packageService,
        private SiteFinder $siteFinder,
    ) {}

    public function getDefaultSiteIdentifier(): ?string
    {
        foreach ($this->siteFinder->getAllSites(true) as $site) {
            $configuration = $site->getConfiguration();
            if (!empty($configuration['assistDefault'])) {
                return $site->getIdentifier();
            }
        }
        return null;
    }

    public function getSiteConfiguration(string $siteIdentifier): ?array
    {
        $site = $this->siteFinder->getSiteByIdentifier($siteIdentifier);
        $configuration = $site->getConfiguration();
        if (!empty($configuration['assistPlatforms'])) {
            return $configuration;
        }
        return null;
    }

    /**
     * Resolves the model for an assistant from the default site configuration.
     */
    public function getDefaultAssistantModel(string $assistantIdentifier): ?PlatformModel
    {
        $identifier = $this->getDefaultSiteIdentifier();
        if ($identifier !== null) {
            return $this->getSiteAssistantModel($identifier, $assistantIdentifier);
        }
        return null;
    }

    /**
     * Resolves the model for an assistant from a specific site configuration.
     */
    public function getSiteAssistantModel(string $siteIdentifier, string $assistantIdentifier): ?PlatformModel
    {
        $site = $this->siteFinder->getSiteByIdentifier($siteIdentifier);
        $configuration = $site->getConfiguration();
        $model = $configuration['assistAssistants'][$assistantIdentifier]['model'] ?? '';
        if ($model === '') {
            return null;
        }
        return PlatformModel::fromString($model);
    }

    /**
     * Resolves the platforms of the site configuration that is declared to be default.
     *
     * @return list<Platform>
     */
    public function getDefaultPlatforms(): array
    {
        $identifier = $this->getDefaultSiteIdentifier();
        if ($identifier !== null) {
            return $this->getSitePlatforms($identifier);
        }
        return [];
    }

    public function getSitePlatform(string $siteIdentifier, string $platformIdentifier): ?Platform
    {
        $platforms = $this->getSitePlatforms($siteIdentifier);
        foreach ($platforms as $platform) {
            if ($platform->package === $platformIdentifier) {
                return $platform;
            }
        }
        return null;
    }

    /**
     * Resolves the platforms of a particular site configuration.
     *
     * @param string $siteIdentifier
     * @return list<Platform>
     */
    public function getSitePlatforms(string $siteIdentifier): array
    {
        $configuration = $this->getSiteConfiguration($siteIdentifier);
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
        // @todo add array key here, for faster platform lookup
        return array_map($this->buildPlatform(...), $platformsConfiguration);
    }

    private function buildPlatform(array $data): Platform
    {
        $options = array_diff_key($data, array_flip(self::PACKAGE_STRUCTURAL_KEYS));
        $options = array_filter($options, static fn($v) => $v !== '' && $v !== null);

        if (!$this->packageService->hasPackage($data['package'])) {
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
            models: $data['models'] ?? [],
        );
    }
}
