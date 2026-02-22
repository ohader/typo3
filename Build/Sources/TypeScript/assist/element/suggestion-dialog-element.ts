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

import { css, html, LitElement, nothing, type PropertyValues, type TemplateResult } from 'lit';
import { customElement, property, state } from 'lit/decorators.js';
import '@typo3/backend/element/spinner-element';
import '@typo3/backend/element/icon-element';

/**
 * Module: @typo3/assist/element/suggestion-dialog-element
 *
 * Modal dialog showing AI-generated text suggestions as a radio-button list.
 * Uses the native <dialog> element for free backdrop, focus-trap and Escape-key
 * handling.  Open/close is controlled via the `open` property.
 *
 * CSS custom properties:
 *   --assist-dialog-header-bg        Header background (default: #1a1a1a)
 *   --assist-dialog-header-color     Header text colour (default: #fff)
 *   --assist-dialog-accent           Accent / highlight colour (default: #f47f00)
 *   --assist-dialog-border-radius    Dialog border radius (default: 4px)
 *   --assist-dialog-max-width        Maximum dialog width (default: 600px)
 *
 * Events:
 *   assist:regenerate   – Regenerate button clicked
 *   assist:cancel       – Cancel button clicked / dialog closed
 *   assist:confirm      – Replace button clicked; detail: { value: string, index: number }
 *
 * @example
 * <typo3-assist-suggestion-dialog
 *   open
 *   heading="TYPO3 AI Assist"
 *   description="Assist will create a short SEO-friendly description."
 *   .suggestions=${['Suggestion A', 'Suggestion B']}
 * ></typo3-assist-suggestion-dialog>
 */
@customElement('typo3-assist-suggestion-dialog')
export class SuggestionDialogElement extends LitElement {
  static override styles = css`
    :host {
      --assist-dialog-header-bg: #1a1a1a;
      --assist-dialog-header-color: #fff;
      --assist-dialog-accent: #f47f00;
      --assist-dialog-border-radius: 4px;
      --assist-dialog-max-width: 600px;
    }

    dialog {
      border: none;
      border-radius: var(--assist-dialog-border-radius);
      padding: 0;
      max-width: var(--assist-dialog-max-width);
      width: calc(100vw - 2rem);
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.32);
      overflow: hidden;
    }

    dialog::backdrop {
      background: rgba(0, 0, 0, 0.5);
    }

    .dialog-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0.75rem 1rem;
      background: var(--assist-dialog-header-bg);
      color: var(--assist-dialog-header-color);
    }

    .dialog-header-title {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      font-weight: 600;
      font-size: 0.9375rem;
      margin: 0;
    }

    .dialog-close {
      display: flex;
      align-items: center;
      justify-content: center;
      background: none;
      border: none;
      color: var(--assist-dialog-header-color);
      cursor: pointer;
      padding: 0.25rem;
      border-radius: 2px;
      opacity: 0.8;
    }

    .dialog-close:hover {
      opacity: 1;
    }

    .dialog-body {
      padding: 1rem;
      background: var(--typo3-component-bg, #fff);
      color: var(--typo3-component-color, #333);
    }

    .dialog-description {
      margin: 0 0 1rem;
      font-size: 0.875rem;
      color: var(--typo3-muted-color, #6c757d);
    }

    .suggestions-label {
      font-weight: 600;
      font-size: 0.8125rem;
      margin-bottom: 0.5rem;
      text-transform: uppercase;
      letter-spacing: 0.03em;
      color: var(--typo3-muted-color, #6c757d);
    }

    .suggestions-list {
      list-style: none;
      margin: 0 0 1rem;
      padding: 0;
      display: flex;
      flex-direction: column;
      gap: 0.375rem;
    }

    .suggestion-item {
      display: flex;
      align-items: flex-start;
      gap: 0.5rem;
      padding: 0.5rem 0.75rem;
      border: 1px solid var(--typo3-component-border-color, #dee2e6);
      border-radius: 3px;
      cursor: pointer;
      transition: border-color 0.1s ease-in-out;
    }

    .suggestion-item:has(input:checked) {
      border-color: var(--assist-dialog-accent);
      background: color-mix(in srgb, var(--assist-dialog-accent) 6%, transparent);
    }

    .suggestion-item input[type="radio"] {
      margin-top: 0.2rem;
      flex-shrink: 0;
      accent-color: var(--assist-dialog-accent);
    }

    .suggestion-text {
      font-size: 0.875rem;
      line-height: 1.5;
    }

    .loading-wrapper {
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 2rem;
    }

    .disclaimer {
      font-size: 0.75rem;
      color: var(--typo3-muted-color, #6c757d);
      margin: 0 0 1rem;
      font-style: italic;
    }

    .dialog-separator {
      border: none;
      border-top: 1px solid var(--typo3-component-border-color, #dee2e6);
      margin: 0.75rem 0;
    }

    .dialog-footer {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.5rem;
    }

    .footer-actions-left {
      display: flex;
      gap: 0.5rem;
    }

    .footer-actions-right {
      display: flex;
      gap: 0.5rem;
    }

    .btn {
      display: inline-flex;
      align-items: center;
      gap: 0.375rem;
      padding: 0.375rem 0.75rem;
      border-radius: 3px;
      font-size: 0.8125rem;
      font-family: inherit;
      font-weight: 500;
      line-height: 1.5;
      cursor: pointer;
      transition: opacity 0.15s ease-in-out;
      border: 1px solid transparent;
    }

    .btn-default {
      background: var(--typo3-component-bg, #e9ecef);
      color: var(--typo3-component-color, #333);
      border-color: var(--typo3-component-border-color, #ced4da);
    }

    .btn-default:hover {
      opacity: 0.8;
    }

    .btn-primary {
      background: var(--assist-dialog-accent);
      color: #fff;
      border-color: var(--assist-dialog-accent);
    }

    .btn-primary:hover:not(:disabled) {
      opacity: 0.9;
    }

    .btn-primary:disabled {
      opacity: 0.5;
      cursor: not-allowed;
    }

    .slot-content {
      margin-top: 0.75rem;
    }
  `;

  @property({ type: Boolean, reflect: true }) open: boolean = false;
  @property({ type: String }) heading: string = 'TYPO3 AI Assist';
  @property({ type: String }) description: string = '';
  @property({ type: Array }) suggestions: string[] = [];
  @property({ type: Boolean }) loading: boolean = false;

  @state() private selectedIndex: number = 0;

  private get dialogEl(): HTMLDialogElement | null {
    return this.shadowRoot?.querySelector('dialog') ?? null;
  }

  protected override updated(changed: PropertyValues): void {
    super.updated(changed);
    if (changed.has('open')) {
      const dialog = this.dialogEl;
      if (!dialog) {
        return;
      }
      if (this.open) {
        if (!dialog.open) {
          dialog.showModal();
        }
        this.selectedIndex = 0;
      } else {
        if (dialog.open) {
          dialog.close();
        }
      }
    }
  }

  protected override render(): TemplateResult {
    return html`
      <dialog @cancel=${this.handleDialogCancel}>
        <div class="dialog-header">
          <h2 class="dialog-header-title">
            <typo3-backend-icon identifier="module-assist" size="small"></typo3-backend-icon>
            ${this.heading}
          </h2>
          <button type="button" class="dialog-close" @click=${this.handleClose} aria-label="Close">
            <typo3-backend-icon identifier="actions-close" size="small"></typo3-backend-icon>
          </button>
        </div>

        <div class="dialog-body">
          ${this.description ? html`<p class="dialog-description">${this.description}</p>` : nothing}

          ${this.loading ? this.renderLoadingState() : this.renderSuggestions()}

          <p class="disclaimer">AI may be wrong. Verify key details before using.</p>

          <hr class="dialog-separator" />

          <div class="dialog-footer">
            <div class="footer-actions-left">
              <button type="button" class="btn btn-default" @click=${this.handleRegenerate}>
                <typo3-backend-icon identifier="actions-refresh" size="small"></typo3-backend-icon>
                Regenerate
              </button>
            </div>
            <div class="footer-actions-right">
              <button type="button" class="btn btn-default" @click=${this.handleClose}>Cancel</button>
              <button
                type="button"
                class="btn btn-primary"
                ?disabled=${this.loading || this.suggestions.length === 0}
                @click=${this.handleConfirm}
              >
                Replace field
              </button>
            </div>
          </div>

          <slot class="slot-content"></slot>
        </div>
      </dialog>
    `;
  }

  private handleClose(): void {
    this.open = false;
    this.dispatchEvent(new CustomEvent('assist:cancel', { bubbles: true, composed: true }));
  }

  private handleRegenerate(): void {
    this.dispatchEvent(new CustomEvent('assist:regenerate', { bubbles: true, composed: true }));
  }

  private handleConfirm(): void {
    const value = this.suggestions[this.selectedIndex] ?? '';
    this.dispatchEvent(new CustomEvent('assist:confirm', {
      bubbles: true,
      composed: true,
      detail: { value, index: this.selectedIndex },
    }));
    this.open = false;
  }

  private handleDialogCancel(e: Event): void {
    e.preventDefault();
    this.handleClose();
  }

  private renderLoadingState(): TemplateResult {
    return html`
      <div class="loading-wrapper" aria-busy="true">
        <typo3-backend-spinner size="default"></typo3-backend-spinner>
      </div>
    `;
  }

  private renderSuggestions(): TemplateResult {
    return html`
      <div class="suggestions-label">Suggested content</div>
      <ul class="suggestions-list" role="radiogroup">
        ${this.suggestions.map((suggestion, index) => html`
          <li class="suggestion-item" @click=${() => { this.selectedIndex = index; }}>
            <input
              type="radio"
              name="assist-suggestion"
              .checked=${this.selectedIndex === index}
              @change=${() => { this.selectedIndex = index; }}
              aria-label=${suggestion}
            />
            <span class="suggestion-text">${suggestion}</span>
          </li>
        `)}
      </ul>
    `;
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-assist-suggestion-dialog': SuggestionDialogElement;
  }
}
