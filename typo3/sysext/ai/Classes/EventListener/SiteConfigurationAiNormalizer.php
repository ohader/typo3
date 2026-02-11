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

namespace TYPO3\CMS\AI\EventListener;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Configuration\Event\SiteConfigurationBeforeWriteEvent;
use TYPO3\CMS\Core\Configuration\Event\SiteConfigurationLoadedEvent;

/**
 * Transforms between nested `ai:` YAML structure (on disk) and flat TCA
 * fields (for the site configuration form).
 *
 * On load:  ai: {default, platforms: [...]} → aiDefault, aiPlatforms: [...]
 * On write: aiDefault, aiPlatforms: [...] → ai: {default, platforms: [...]}
 */
final class SiteConfigurationAiNormalizer
{

    /**
     * Flatten nested ai: structure into flat TCA keys for the form.
     */
    #[AsEventListener('typo3/cms-ai/site-configuration-loaded')]
    public function onSiteConfigurationLoaded(SiteConfigurationLoadedEvent $event): void
    {
        $configuration = $event->getConfiguration();
        if (!isset($configuration['ai'])) {
            return;
        }

        $ai = $configuration['ai'];
        $configuration['aiDefault'] = (bool)($ai['default'] ?? false);

        $platforms = [];
        foreach ($ai['platforms'] ?? [] as $index => $platform) {
            $platforms[$index] = [
                'enabled' => (bool)($platform['enabled'] ?? false),
                'name' => (string)($platform['name'] ?? ''),
                'package' => (string)($platform['package'] ?? ''),
                'baseUrl' => (string)($platform['options']['baseUrl'] ?? ''),
                'authorizationType' => (string)($platform['authorization']['type'] ?? 'none'),
                'authorizationToken' => (string)($platform['authorization']['token'] ?? ''),
            ];
        }
        $configuration['aiPlatforms'] = $platforms;

        unset($configuration['ai']);
        $event->setConfiguration($configuration);
    }

    /**
     * Nest flat TCA keys back into ai: YAML structure before writing.
     */
    #[AsEventListener('typo3/cms-ai/site-configuration-before-write')]
    public function onSiteConfigurationBeforeWrite(SiteConfigurationBeforeWriteEvent $event): void
    {
        $configuration = $event->getConfiguration();
        if (!array_key_exists('aiDefault', $configuration)
            && !array_key_exists('aiPlatforms', $configuration)
        ) {
            return;
        }

        $ai = [];
        $ai['default'] = (bool)($configuration['aiDefault'] ?? false);

        $platforms = [];
        foreach ($configuration['aiPlatforms'] ?? [] as $platform) {
            $entry = [
                'enabled' => (bool)($platform['enabled'] ?? false),
                'name' => $platform['name'] ?? '',
                'package' => $platform['package'] ?? '',
            ];
            if (!empty($platform['baseUrl'])) {
                $entry['options'] = [
                    'baseUrl' => $platform['baseUrl'],
                ];
            }
            $authType = $platform['authorizationType'] ?? 'none';
            if ($authType !== 'none') {
                $entry['authorization'] = [
                    'type' => $authType,
                    'token' => $platform['authorizationToken'] ?? '',
                ];
            }
            $platforms[] = $entry;
        }
        $ai['platforms'] = $platforms;

        $configuration['ai'] = $ai;
        unset($configuration['aiDefault'], $configuration['aiPlatforms']);
        $event->setConfiguration($configuration);
    }
}
