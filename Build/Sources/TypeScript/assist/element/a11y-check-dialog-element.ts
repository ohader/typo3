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
import { customElement, property } from 'lit/decorators.js';
import '@typo3/backend/element/icon-element';

/**
 * Accessibility check item definition.
 */
export interface A11yCheckItem {
  id: string;
  label: string;
  sublabel?: string;
  checked: boolean;
}

const DEFAULT_CHECKS: A11yCheckItem[] = [
  { id: 'images', label: 'Images', sublabel: 'Missing or weak alt texts', checked: true },
  { id: 'link-texts', label: 'Link texts', sublabel: 'Vague meanings', checked: true },
  { id: 'headings', label: 'Heading structure', sublabel: 'Hierarchy', checked: true },
  { id: 'color-contrast', label: 'Color contrast issues', checked: false },
  { id: 'form-labels', label: 'Form labels & input descriptions', checked: false },
  { id: 'language', label: 'General language clarity', sublabel: 'Complexity', checked: false },
];

/**
 * Module: @typo3/assist/element/a11y-check-dialog-element
 *
 * Pre-flight configuration dialog listing which accessibility checks to run.
 * Uses the native <dialog> element for backdrop, focus-trap and Escape handling.
 *
 * CSS custom properties: shared --assist-dialog-* set (same as suggestion-dialog).
 *
 * Events:
 *   assist:run-check   – Run button clicked; detail: { checks: A11yCheckItem[] } (checked only)
 *   assist:cancel      – Cancel button clicked / dialog closed
 *
 * @example
 * <typo3-assist-a11y-check-dialog open></typo3-assist-a11y-check-dialog>
 */
@customElement('typo3-assist-a11y-check-dialog')
export class A11yCheckDialogElement extends LitElement {
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

    .dialog-intro {
      font-size: 0.875rem;
      color: var(--typo3-muted-color, #6c757d);
      margin: 0 0 1rem;
    }

    .checks-list {
      list-style: none;
      margin: 0 0 1rem;
      padding: 0;
      display: flex;
      flex-direction: column;
      gap: 0;
    }

    .check-item {
      display: flex;
      align-items: flex-start;
      gap: 0.625rem;
      padding: 0.625rem 0;
      border-bottom: 1px solid var(--typo3-component-border-color, #dee2e6);
    }

    .check-item:last-child {
      border-bottom: none;
    }

    .check-item input[type="checkbox"] {
      margin-top: 0.15rem;
      flex-shrink: 0;
      accent-color: var(--assist-dialog-accent);
    }

    .check-labels {
      display: flex;
      flex-direction: column;
      gap: 0.125rem;
    }

    .check-label {
      font-size: 0.875rem;
      font-weight: 500;
      line-height: 1.4;
    }

    .check-sublabel {
      font-size: 0.8125rem;
      color: var(--typo3-muted-color, #6c757d);
    }

    .dialog-separator {
      border: none;
      border-top: 1px solid var(--typo3-component-border-color, #dee2e6);
      margin: 0.75rem 0;
    }

    .dialog-footer {
      display: flex;
      align-items: center;
      justify-content: flex-end;
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
      border: 1px solid transparent;
      transition: opacity 0.15s ease-in-out;
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
  `;

  @property({ type: Boolean, reflect: true }) open: boolean = false;
  @property({ type: Array }) checks: A11yCheckItem[] = DEFAULT_CHECKS.map(c => ({ ...c }));

  private get dialogEl(): HTMLDialogElement | null {
    return this.shadowRoot?.querySelector('dialog') ?? null;
  }

  private get hasCheckedItems(): boolean {
    return this.checks.some(c => c.checked);
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
            Accessibility Review
          </h2>
          <button type="button" class="dialog-close" @click=${this.handleClose} aria-label="Close">
            <typo3-backend-icon identifier="actions-close" size="small"></typo3-backend-icon>
          </button>
        </div>

        <div class="dialog-body">
          <p class="dialog-intro">
            Select which accessibility checks you want to run on the current page.
          </p>

          <ul class="checks-list">
            ${this.checks.map(item => html`
              <li class="check-item">
                <input
                  type="checkbox"
                  id="check-${item.id}"
                  .checked=${item.checked}
                  @change=${(e: Event) => this.handleCheckChange(item.id, (e.target as HTMLInputElement).checked)}
                />
                <div class="check-labels">
                  <label class="check-label" for="check-${item.id}">${item.label}</label>
                  ${item.sublabel ? html`<span class="check-sublabel">${item.sublabel}</span>` : nothing}
                </div>
              </li>
            `)}
          </ul>

          <hr class="dialog-separator" />

          <div class="dialog-footer">
            <button type="button" class="btn btn-default" @click=${this.handleClose}>Cancel</button>
            <button
              type="button"
              class="btn btn-primary"
              ?disabled=${!this.hasCheckedItems}
              @click=${this.handleRunCheck}
            >
              Run accessibility review
            </button>
          </div>
        </div>
      </dialog>
    `;
  }

  private handleClose(): void {
    this.open = false;
    this.dispatchEvent(new CustomEvent('assist:cancel', { bubbles: true, composed: true }));
  }

  private handleDialogCancel(e: Event): void {
    e.preventDefault();
    this.handleClose();
  }

  private handleCheckChange(id: string, checked: boolean): void {
    this.checks = this.checks.map(c => c.id === id ? { ...c, checked } : c);
  }

  private handleRunCheck(): void {
    const checkedItems = this.checks.filter(c => c.checked);
    this.dispatchEvent(new CustomEvent('assist:run-check', {
      bubbles: true,
      composed: true,
      detail: { checks: checkedItems },
    }));
    this.open = false;
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-assist-a11y-check-dialog': A11yCheckDialogElement;
  }
}
