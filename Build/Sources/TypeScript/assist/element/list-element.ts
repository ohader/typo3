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

export interface ListFeedbackItem {
  type: 'list';
  items: string[];
}

/**
 * Module: @typo3/assist/element/list-element
 */
@customElement('typo3-assist-list-element')
export class ListElement extends LitElement {
  @property({ type: Array }) items: string[] = [];

  override createRenderRoot(): HTMLElement | DocumentFragment {
    return this;
  }

  protected override render(): TemplateResult {
    return html`
      <ul class="assist-chat__list">
        ${this.items.map(item => html`<li class="assist-chat__list-item">${item}</li>`)}
      </ul>
    `;
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-assist-list-element': ListElement;
  }
}
