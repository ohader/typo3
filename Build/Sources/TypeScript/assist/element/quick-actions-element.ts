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

import { customElement, property, state } from 'lit/decorators.js';
import { html, LitElement, nothing, type TemplateResult } from 'lit';
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

  @state() private selectedIdentifier: string | null = null;

  override createRenderRoot(): HTMLElement | DocumentFragment {
    return this;
  }

  protected override render(): TemplateResult {
    return html`
      ${this.text ? html`<p class="assist-chat__text">${this.text}</p>` : nothing}
      <ul class="assist-chat__quick-actions">
        ${this.items.map((item) => html`
          <li><a href="#" class="assist-chat__quick-action ${this.selectedIdentifier !== null ? 'disabled' : ''}"
                 aria-disabled=${this.selectedIdentifier !== null ? 'true' : nothing}
                 @click=${(e: Event) => this.handleSelection(e, item)}>${item.text}</a>
          </li>
        `)}
      </ul>
    `;
  }

  private handleSelection(e: Event, item: QuickActionItemData): void {
    e.preventDefault();
    if (this.selectedIdentifier !== null) {
      return;
    }
    this.selectedIdentifier = item.identifier;
    const recover = () => { this.selectedIdentifier = null; };
    this.dispatchEvent(new CustomEvent<{ key: string; identifier: string; text: string; recover: () => void }>('typo3-assist-quick-action-select', {
      detail: { key: this.key, identifier: item.identifier, text: item.text, recover },
      bubbles: true,
    }));
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-assist-quick-actions-element': QuickActionsElement;
  }
}
