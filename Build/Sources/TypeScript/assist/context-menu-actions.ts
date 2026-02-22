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

export interface AssistantContextMenuInvokable {
  invokeFromContextMenu(table: string, uid: number, dataset: DOMStringMap): void;
}

/**
 * Callback module for context menu items registered by EXT:assist.
 *
 * Invoked by the TYPO3 context menu when the user clicks an assistant entry.
 * Dynamically imports the assistant's module and delegates to its `invokeFromContextMenu()` method.
 */
export class ContextMenuActions {
  openAssistant(table: string, uid: number, dataset: DOMStringMap): void {
    // @todo divert to corresponding backend module view (if non-inline)
    const assistantModule = dataset.assistantModule ?? '';
    if (!assistantModule) {
      return;
    }
    import(assistantModule + '.js').then(
      ({ default: actions }: { default: AssistantContextMenuInvokable }): void => {
        actions.invokeFromContextMenu(table, uid, dataset);
      }
    );
  }
}

export default new ContextMenuActions();
