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

namespace TYPO3\CMS\Assist\Tests\Functional;

use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;

trait AssistBasedTestTrait
{
    use SiteBasedTestTrait;
    private const ASSIST_MODEL_NUMB_ENCORE = 'numb-encore';
    private const ASSIST_PLATFORM_NUMB_ENCORE = 'assist-platform-numb-encore';

    private function buildAssistSiteConfiguration(
        string $identifier,
        int $rootPageId,
        string $base,
        array $aspects,
    ): void {
        $siteConfiguration = [
            'rootPageId' => $rootPageId,
            'base' => $base,
            'assist' => [
                'default' => true,
                'platforms' => [],
            ],
        ];
        if (in_array(self::ASSIST_PLATFORM_NUMB_ENCORE, $aspects, true)) {
            $siteConfiguration['assist']['platforms'][] = [
                'enabled' => true,
                'name' => 'TYPO3 Numb Encore',
                'package' => 'typo3/symfony-ai-numb-platform',
                'models' => [
                    'numb-encore',
                ],
            ];
        }
        $this->writeSiteConfiguration($identifier, $siteConfiguration);
    }
}
