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

use TYPO3\CMS\Assist\Assistant\A11yAssistant;
use TYPO3\CMS\Assist\Assistant\InlineChatAssistant;
use TYPO3\CMS\Assist\Domain\AssistantCapability;

return [
    'typo3-a11y' => [
        'mode' => 'module',
        'capabilities' => [AssistantCapability::messages, AssistantCapability::inputImage, AssistantCapability::toolCalling],
        'handler' => A11yAssistant::class,
        'trigger' => [
            'types' => ['context', 'view'],
            'records' => ['pages'],
            'components' => ['page-tree'],
        ],
    ],
    'typo3-inline-chat' => [
        'mode' => 'inline',
        'capabilities' => [AssistantCapability::messages, AssistantCapability::toolCalling],
        'handler' => InlineChatAssistant::class,
        'trigger' => [
            'types' => ['inline'],
            'records' => ['pages', 'tt_content'],
        ],
    ],
];
