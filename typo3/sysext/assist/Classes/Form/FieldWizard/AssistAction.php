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

namespace TYPO3\CMS\Assist\Form\FieldWizard;

use TYPO3\CMS\Backend\Form\AbstractNode;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;

/**
 * Renders a <typo3-assist-action> custom element below a TCA field.
 * Register as a fieldWizard renderType "assistAction".
 */
class AssistAction extends AbstractNode
{
    public function render(): array
    {
        $result = $this->initializeResultArray();

        $table = $this->data['tableName'];
        $uid = $this->data['databaseRow']['uid'] ?? 0;
        $label = $this->getLanguageService()->sL(
            'LLL:EXT:assist/Resources/Private/Language/locallang_db.xlf:fieldWizard.assistAction.button'
        );

        $triggerResource = htmlspecialchars($table . ':' . $uid, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $labelAttr = htmlspecialchars((string)$label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $result['html'] = sprintf(
            '<typo3-assist-action trigger-resource="%s" trigger-component="resource-edit" label="%s"></typo3-assist-action>',
            $triggerResource,
            $labelAttr
        );

        $result['javaScriptModules'][] = JavaScriptModuleInstruction::create(
            '@typo3/assist/element/assist-action-element.js'
        );

        return $result;
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
