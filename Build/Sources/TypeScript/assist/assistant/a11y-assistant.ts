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

import type { AssistantContextMenuInvokable } from '@typo3/assist/context-menu-actions';
import type { A11yCheckDialogElement } from '@typo3/assist/element/a11y-check-dialog-element';

export class A11yAssistant implements AssistantContextMenuInvokable {
  invokeFromContextMenu(table: string, uid: number, dataset: DOMStringMap): void {
    console.log(dataset);
    import('@typo3/assist/element/a11y-check-dialog-element').then(
      (): void => {
        let dialog = document.querySelector<A11yCheckDialogElement>('typo3-assist-a11y-check-dialog');
        if (dialog === null) {
          dialog = document.createElement('typo3-assist-a11y-check-dialog') as A11yCheckDialogElement;
          document.body.appendChild(dialog);
        }
        dialog.open = true;
      }
    );
  }
}

export default new A11yAssistant();
