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

import { html, LitElement, type TemplateResult } from 'lit';
import { customElement, property } from 'lit/decorators.js';
import '@typo3/backend/element/icon-element';

/**
 * Module: @typo3/assist/element/assist-action-element
 *
 * Reusable entry-point for contextual AI assistance triggered from within
 * form fields. Renders an "Assist" button that can be placed below a TCA
 * field via the standard fieldWizard mechanism.
 *
 * @example
 * <typo3-assist-action
 *   trigger-resource="tt_content:42"
 *   trigger-component="resource-edit"
 *   label="Assist"
 * ></typo3-assist-action>
 */
@customElement('typo3-assist-action')
export class AssistActionElement extends LitElement {
  @property({ type: String, attribute: 'trigger-resource' }) triggerResource: string = '';
  @property({ type: String, attribute: 'trigger-component' }) triggerComponent: string = '';
  @property({ type: String }) label: string = 'Assist';

  override createRenderRoot(): HTMLElement {
    return this;
  }

  protected override render(): TemplateResult {
    return html`
      <button type="button" class="btn btn-info btn-sm" @click=${this.handleClick}>
        <typo3-backend-icon identifier="module-assist" size="small"></typo3-backend-icon>
        ${this.label}
      </button>
    `;
  }

  private handleClick(): void {
    console.log('assist');
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-assist-action': AssistActionElement;
  }
}
