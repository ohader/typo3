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

import { css, html, LitElement, nothing, type TemplateResult } from 'lit';
import { customElement, property } from 'lit/decorators.js';
import '@typo3/backend/element/spinner-element';
import '@typo3/backend/element/icon-element';

/**
 * Module: @typo3/assist/element/generate-button-element
 *
 * Inline trigger button that places an "Generate with Assist" call-to-action
 * next to form fields. Renders the Assist star icon and a text label.
 *
 * CSS custom properties:
 *   --assist-generate-bg            Button background (default: #f47f00)
 *   --assist-generate-color         Button text/icon colour (default: #fff)
 *   --assist-generate-border-radius Button border radius (default: 3px)
 *
 * @example
 * <typo3-assist-generate-button label="Generate with Assist"></typo3-assist-generate-button>
 * <typo3-assist-generate-button loading></typo3-assist-generate-button>
 * <typo3-assist-generate-button disabled></typo3-assist-generate-button>
 */
@customElement('typo3-assist-generate-button')
export class GenerateButtonElement extends LitElement {
  static override styles = css`
    :host {
      --assist-generate-bg: #f47f00;
      --assist-generate-color: #fff;
      --assist-generate-border-radius: 3px;
      display: inline-block;
    }

    :host([hidden]) {
      display: none;
    }

    .btn {
      display: inline-flex;
      align-items: center;
      gap: 0.375rem;
      padding: 0.25rem 0.625rem;
      border: none;
      border-radius: var(--assist-generate-border-radius);
      background: var(--assist-generate-bg);
      color: var(--assist-generate-color);
      font-size: 0.8125rem;
      font-family: inherit;
      font-weight: 500;
      line-height: 1.5;
      cursor: pointer;
      white-space: nowrap;
      transition: opacity 0.15s ease-in-out;
      text-decoration: none;
    }

    .btn:hover:not(:disabled) {
      opacity: 0.9;
    }

    .btn:focus-visible {
      outline: 2px solid var(--assist-generate-bg);
      outline-offset: 2px;
    }

    .btn:disabled {
      opacity: 0.6;
      cursor: not-allowed;
    }

    .btn-icon {
      display: flex;
      align-items: center;
      flex-shrink: 0;
      color: var(--assist-generate-color);
    }

    typo3-backend-spinner,
    typo3-backend-icon {
      color: var(--assist-generate-color);
    }
  `;

  @property({ type: String }) label: string = 'Generate with Assist';
  @property({ type: Boolean, reflect: true }) loading: boolean = false;
  @property({ type: Boolean, reflect: true }) disabled: boolean = false;

  protected override render(): TemplateResult {
    return html`
      <button
        type="button"
        class="btn"
        ?disabled=${this.disabled || this.loading}
        aria-busy=${this.loading ? 'true' : 'false'}
      >
        <span class="btn-icon">
          ${this.loading ? html`<typo3-backend-spinner size="small"></typo3-backend-spinner>` : html`<typo3-backend-icon identifier="module-assist" size="small"></typo3-backend-icon>`}
        </span>
        ${this.label ? html`<span class="btn-label">${this.label}</span>` : nothing}
      </button>
    `;
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-assist-generate-button': GenerateButtonElement;
  }
}
