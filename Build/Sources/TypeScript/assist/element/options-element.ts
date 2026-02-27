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
import { html, LitElement, nothing, type TemplateResult } from 'lit';
// import { LabelProvider } from '@typo3/backend/localization/label-provider';
import labels from '~labels/assist.elements';

interface OptionItem {
  text: string;
  details?: string;
}

/**
 * Module: @typo3/assist/element/options-element
 */
@customElement('typo3-assist-options-element')
export class OptionsElement extends LitElement {
  @property({ type: String }) text: string = '';
  @property({ type: Array }) options: OptionItem[] = [];

  override createRenderRoot(): HTMLElement | DocumentFragment {
    return this;
  }

  protected override render(): TemplateResult {
    return html`
      <p class="assist-chat__text">${this.text}</p>
      <div class="assist-chat__options">
        ${this.options.map((option, index) => html`
          <article class="panel panel-default assist-chat__option">
            <div class="panel-heading">
              <h3 class="h5 assist-chat__option-title">Option ${this.indexToChar(index)} - ${option.text}</h3>
            </div>
            ${option.details ? html`
              <div class="panel-body assist-chat__option-text">${option.details}</div>
            ` : nothing}
            <div class="panel-footer assist-chat__option-actions">
              <button type="button" class="assist-chat__option-action btn btn-default">${labels.get('button.accept')}</button>
            </div>
          </article>
        `)}
      </div>
    `;
  }

  private indexToChar(index: number): string {
    if (index < 0 || index > 25) {
      return index.toString();
    }
    return String.fromCharCode(65 + index);
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-assist-options-element': OptionsElement;
  }
}
