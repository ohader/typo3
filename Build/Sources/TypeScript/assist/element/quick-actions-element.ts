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

import { customElement, property } from 'lit/decorators.js';
import { html, LitElement, type TemplateResult } from 'lit';
// import { LabelProvider } from '@typo3/backend/localization/label-provider';
// import labels from '~labels/assist.elements';

export interface QuickActionItemData {
  identifier: string;
  text: string;
}

export interface QuickActionsFeedbackItem {
  type: 'quick-actions';
  key: string;
  text: string;
  items: QuickActionItemData[];
}


/**
 * Module: @typo3/assist/element/quick-actions-element
 */
@customElement('typo3-assist-quick-actions-element')
export class QuickActionsElement extends LitElement {
  @property({ type: String }) key: string = '';
  @property({ type: String }) text: string = '';
  @property({ type: Array }) items: QuickActionItemData[] = [];

  override createRenderRoot(): HTMLElement | DocumentFragment {
    return this;
  }

  protected override render(): TemplateResult {
    return html`
      <ul class="assist-chat__quick-actions">
        ${this.items.map((item) => html`
          <li><a href="#" class="assist-chat__quick-action"
                 @click=${() => this.handleSelection(item)}>${item.text}</a>
          </li>
        `)}
      </ul>
    `;
  }

  private handleSelection(item: QuickActionItemData): void {
    this.dispatchEvent(new CustomEvent<{ key: string; identifier: string; text: string }>('typo3-assist-quick-action-select', {
      detail: { key: this.key, identifier: item.identifier, text: item.text },
      bubbles: true,
    }));
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-assist-quick-actions-element': QuickActionsElement;
  }
}
