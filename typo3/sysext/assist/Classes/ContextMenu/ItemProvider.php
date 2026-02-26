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

namespace TYPO3\CMS\Assist\ContextMenu;

use TYPO3\CMS\Assist\Service\AssistantRegistry;
use TYPO3\CMS\Backend\ContextMenu\ItemProviders\AbstractProvider;

/**
 * Context menu item provider that adds assistant entries for the 'context-menu' trigger component.
 *
 * @internal This class is not considered part of the public TYPO3 API.
 */
final class ItemProvider extends AbstractProvider
{
    public function __construct(
        private readonly AssistantRegistry $assistantRegistry,
    ) {
        parent::__construct();
    }

    public function canHandle(): bool
    {
        return $this->assistantRegistry->getAssistantsByTriggerComponent('context-menu') !== [];
    }

    public function getPriority(): int
    {
        return 45;
    }

    public function addItems(array $items): array
    {
        $this->initDisabledItems();
        $prepared = $this->prepareItems($this->buildItemsConfiguration());

        if (($prepared['assistAi']['childItems'] ?? []) === []) {
            return $items;
        }

        // Insert the section immediately after 'more', or append if 'more' is absent
        $result = [];
        $inserted = false;
        foreach ($items as $key => $value) {
            $result[$key] = $value;
            if ($key === 'more' && !$inserted) {
                $result += $prepared;
                $inserted = true;
            }
        }
        if (!$inserted) {
            $result += $prepared;
        }
        return $result;
    }

    protected function canRender(string $itemName, string $type): bool
    {
        if (in_array($itemName, $this->disabledItems, true)) {
            return false;
        }
        if ($type === 'submenu') {
            return true;
        }
        if (!$this->assistantRegistry->hasAssistant($itemName)) {
            return false;
        }
        $assistant = $this->assistantRegistry->getAssistant($itemName);
        if ($assistant->trigger->resources !== [] && !$assistant->trigger->hasResource($this->table)) {
            return false;
        }
        return true;
    }

    protected function getAdditionalAttributes(string $itemName): array
    {
        if (!$this->assistantRegistry->hasAssistant($itemName)) {
            return [];
        }
        $assistant = $this->assistantRegistry->getAssistant($itemName);
        return [
            'data-callback-module' => '@typo3/assist/context-menu-actions',
            'data-assistant-identifier' => $assistant->identifier,
            'data-assistant-module' => $assistant->javaScriptModule ?: null,
        ];
    }

    private function buildItemsConfiguration(): array
    {
        $childItems = [];
        foreach ($this->assistantRegistry->getAssistantsByTriggerComponent('context-menu') as $assistant) {
            $childItems[$assistant->identifier] = [
                'type' => 'item',
                'label' => $assistant->labelFile !== ''
                    ? $this->languageService->sL($assistant->labelFile . ':default')
                    : $assistant->identifier,
                'iconIdentifier' => 'module-assist',
                'callbackAction' => 'openAssistant',
            ];
        }
        return [
            'assistAi' => [
                'type' => 'submenu',
                'label' => 'LLL:EXT:assist/Resources/Private/Language/locallang_db.xlf:contextMenu.aiAssistants',
                'iconIdentifier' => 'module-assist',
                'callbackAction' => 'openSubmenu',
                'childItems' => $childItems,
            ],
        ];
    }
}
