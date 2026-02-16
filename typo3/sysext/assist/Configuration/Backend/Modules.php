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

use TYPO3\CMS\Assist\Controller\Module\AssistantController;
use TYPO3\CMS\Assist\Controller\Module\GlossaryController;
use TYPO3\CMS\Assist\Controller\Module\PlatformController;
use TYPO3\CMS\Assist\Controller\Module\PromptController;

/**
 * Definitions for modules provided by EXT:assist
 */
return [
    'assist' => [
        'position' => ['after' => 'site'],
        'labels' => 'assist.modules.assist',
        'iconIdentifier' => 'module-assist',
        'appearance' => [
            'dependsOnSubmodules' => true,
        ],
    ],
    'assist_platform' => [
        'parent' => 'assist',
        'access' => 'admin',
        'path' => '/module/assist/platform',
        'iconIdentifier' => 'module-assist',
        'labels' => 'assist.modules.assist_platform',
        'navigationComponent' => '@typo3/backend/tree/page-tree-element',
        'routes' => [
            '_default' => [
                'target' => PlatformController::class . '::handleRequest',
            ],
        ],
    ],
    'assist_assistant' => [
        'parent' => 'assist',
        'access' => 'admin',
        'path' => '/module/assist/assistant',
        'iconIdentifier' => 'module-assist',
        'labels' => 'assist.modules.assist_assistant',
        'navigationComponent' => '@typo3/backend/tree/page-tree-element',
        'routes' => [
            '_default' => [
                'target' => AssistantController::class . '::handleRequest',
            ],
        ],
    ],
    'assist_glossary' => [
        'parent' => 'assist',
        'access' => 'admin',
        'path' => '/module/assist/glossary',
        'iconIdentifier' => 'module-assist',
        'labels' => 'assist.modules.assist_glossary',
        'navigationComponent' => '@typo3/backend/tree/page-tree-element',
        'routes' => [
            '_default' => [
                'target' => GlossaryController::class . '::handleRequest',
            ],
        ],
    ],
    'assist_prompts' => [
        'parent' => 'assist',
        'access' => 'admin',
        'path' => '/module/assist/prompts',
        'iconIdentifier' => 'module-assist',
        'labels' => 'assist.modules.assist_prompts',
        'navigationComponent' => '@typo3/backend/tree/page-tree-element',
        'routes' => [
            '_default' => [
                'target' => PromptController::class . '::handleRequest',
            ],
        ],
    ],
];
