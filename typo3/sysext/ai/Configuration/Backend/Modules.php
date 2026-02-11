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

use TYPO3\CMS\AI\Controller\Module\GlossaryController;
use TYPO3\CMS\AI\Controller\Module\PlatformController;
use TYPO3\CMS\AI\Controller\Module\PromptController;
use TYPO3\CMS\AI\Controller\Module\TaskController;

/**
 * Definitions for modules provided by EXT:ai
 */
return [
    'ai' => [
        'position' => ['after' => 'site'],
        'labels' => 'ai.modules.ai',
        'iconIdentifier' => 'module-ai',
        'appearance' => [
            'dependsOnSubmodules' => true,
        ],
    ],
    'ai_platform' => [
        'parent' => 'ai',
        'access' => 'admin',
        'path' => '/module/ai/platform',
        'iconIdentifier' => 'module-ai',
        'labels' => 'ai.modules.ai_platform',
        'navigationComponent' => '@typo3/backend/tree/page-tree-element',
        'routes' => [
            '_default' => [
                'target' => PlatformController::class . '::handleRequest',
            ],
        ],
    ],
    'ai_tasks' => [
        'parent' => 'ai',
        'access' => 'admin',
        'path' => '/module/ai/tasks',
        'iconIdentifier' => 'module-ai',
        'labels' => 'ai.modules.ai_tasks',
        'navigationComponent' => '@typo3/backend/tree/page-tree-element',
        'routes' => [
            '_default' => [
                'target' => TaskController::class . '::handleRequest',
            ],
        ],
    ],
    'ai_glossary' => [
        'parent' => 'ai',
        'access' => 'admin',
        'path' => '/module/ai/glossary',
        'iconIdentifier' => 'module-ai',
        'labels' => 'ai.modules.ai_glossary',
        'navigationComponent' => '@typo3/backend/tree/page-tree-element',
        'routes' => [
            '_default' => [
                'target' => GlossaryController::class . '::handleRequest',
            ],
        ],
    ],
    'ai_prompts' => [
        'parent' => 'ai',
        'access' => 'admin',
        'path' => '/module/ai/prompts',
        'iconIdentifier' => 'module-ai',
        'labels' => 'ai.modules.ai_prompts',
        'navigationComponent' => '@typo3/backend/tree/page-tree-element',
        'routes' => [
            '_default' => [
                'target' => PromptController::class . '::handleRequest',
            ],
        ],
    ],
];
