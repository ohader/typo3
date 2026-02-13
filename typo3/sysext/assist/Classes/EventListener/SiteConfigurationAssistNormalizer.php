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

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Configuration\Event\SiteConfigurationBeforeWriteEvent;
use TYPO3\CMS\Core\Configuration\Event\SiteConfigurationLoadedEvent;

/**
 * Transforms between nested `assist:` YAML structure (on disk) and flat TCA
 * fields (for the site configuration form).
 *
 * On load:  assist: {default, platforms: [...]} → assistDefault, assistPlatforms: [...]
 * On write: assistDefault, assistPlatforms: [...] → assist: {default, platforms: [...]}
 */
final class SiteConfigurationAssistNormalizer
{

    /**
     * Flatten nested assist: structure into flat TCA keys for the form.
     */
    #[AsEventListener('typo3/cms-assist/site-configuration-loaded')]
    public function onSiteConfigurationLoaded(SiteConfigurationLoadedEvent $event): void
    {
        $configuration = $event->getConfiguration();
        if (!isset($configuration['assist'])) {
            return;
        }

        $assist = $configuration['assist'];
        $configuration['assistDefault'] = (bool)($assist['default'] ?? false);

        $platforms = [];
        foreach ($assist['platforms'] ?? [] as $index => $platform) {
            $platforms[$index] = [
                'enabled' => (bool)($platform['enabled'] ?? false),
                'name' => (string)($platform['name'] ?? ''),
                'package' => (string)($platform['package'] ?? ''),
                'baseUrl' => (string)($platform['options']['baseUrl'] ?? ''),
                'authorizationType' => (string)($platform['authorization']['type'] ?? 'none'),
                'authorizationToken' => (string)($platform['authorization']['token'] ?? ''),
                'models' => (array)($platform['models'] ?? []),
            ];
        }
        $configuration['assistPlatforms'] = $platforms;

        unset($configuration['assist']);
        $event->setConfiguration($configuration);
    }

    /**
     * Nest flat TCA keys back into assist: YAML structure before writing.
     */
    #[AsEventListener('typo3/cms-assist/site-configuration-before-write')]
    public function onSiteConfigurationBeforeWrite(SiteConfigurationBeforeWriteEvent $event): void
    {
        $configuration = $event->getConfiguration();
        if (!array_key_exists('assistDefault', $configuration)
            && !array_key_exists('assistPlatforms', $configuration)
        ) {
            return;
        }

        $assist = [];
        $assist['default'] = (bool)($configuration['assistDefault'] ?? false);

        $platforms = [];
        foreach ($configuration['assistPlatforms'] ?? [] as $platform) {
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
            if (!empty($platform['models'])) {
                $entry['models'] = array_values($platform['models']);
            }
            $platforms[] = $entry;
        }
        $assist['platforms'] = $platforms;

        $configuration['assist'] = $assist;
        unset($configuration['assistDefault'], $configuration['assistPlatforms']);
        $event->setConfiguration($configuration);
    }
}
