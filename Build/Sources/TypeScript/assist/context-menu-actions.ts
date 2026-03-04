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

import { openAssistModal } from '@typo3/assist/assistant-trigger';
import type { AssistChatProperties } from '@typo3/assist/element/chat-element';

/**
 * Callback module for context menu items registered by EXT:assist.
 *
 * Invoked by the TYPO3 context menu when the user clicks an assistant entry.
 * Opens the central chat widget, optionally loading the assistant's additional module as a side-effect.
 */
export class ContextMenuActions {
  openAssistant(table: string, uid: number, dataset: DOMStringMap): void {
    const subject = JSON.stringify({
      kind: 'tca',
      tableName: table,
      uid,
      propertyName: '',
      flexFormPath: null,
      types: null,
    });
    const properties: AssistChatProperties = {
      additionalModule: dataset.assistantModule ?? '',
      subject,
      assistant: dataset.assistantIdentifier ?? '',
      labelDomain: dataset.assistantLabelDomain ?? '',
    };
    void openAssistModal(properties);
  }
}

export default new ContextMenuActions();
