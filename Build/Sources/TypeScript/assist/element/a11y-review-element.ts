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
import { customElement, property, state } from 'lit/decorators.js';
import { classMap } from 'lit/directives/class-map.js';
import '@typo3/backend/element/spinner-element';
import '@typo3/backend/element/icon-element';

/**
 * A single item shown in the accessibility review step.
 */
export interface ReviewItem {
  id: string;
  filename: string;
  thumbnail?: string;
  currentAlt: string;
  assessment: string;
  assessmentSeverity: 'error' | 'warning' | 'ok';
  suggestions: string[];
}

/**
 * Module: @typo3/assist/element/a11y-review-element
 *
 * Full-width wizard component showing one step of the accessibility review.
 * Displays a list of ReviewItems for the current check category with radio
 * selection for suggested fixes.  Not a modal — designed for embedding in a
 * Fluid module template.
 *
 * CSS custom properties:
 *   --assist-review-accent              Accent colour (default: #f47f00)
 *   --assist-review-warning-bg          Warning item background (default: #fff4e5)
 *   --assist-review-warning-border      Warning item border (default: #f0ad4e)
 *   --assist-review-step-indicator-bg   Step indicator background
 *
 * Events:
 *   assist:previous       – Previous step button clicked
 *   assist:continue       – Continue button clicked; detail: { selections: Record<string, string> }
 *   assist:replace-item   – Single-item apply button; detail: { id: string, value: string }
 *   assist:regenerate-item – Regenerate button for one item; detail: { id: string }
 *
 * @example
 * <typo3-assist-a11y-review
 *   page="typo3.org"
 *   check="Images / Missing or weak alt texts"
 *   step="1"
 *   total-steps="3"
 *   next-check="Link texts / vague meanings"
 *   .items=${reviewItems}
 * ></typo3-assist-a11y-review>
 */
@customElement('typo3-assist-a11y-review')
export class A11yReviewElement extends LitElement {
  static override styles = css`
    :host {
      --assist-review-accent: #f47f00;
      --assist-review-warning-bg: #fff4e5;
      --assist-review-warning-border: #f0ad4e;
      --assist-review-error-bg: #fdf2f2;
      --assist-review-error-border: #e74c3c;
      --assist-review-ok-bg: #f0fff4;
      --assist-review-ok-border: #2ecc71;
      --assist-review-step-indicator-bg: var(--typo3-component-bg, #f8f9fa);
      display: block;
    }

    :host([hidden]) {
      display: none;
    }

    .review-wrapper {
      display: flex;
      flex-direction: column;
      gap: 0;
      border: 1px solid var(--typo3-component-border-color, #dee2e6);
      border-radius: var(--typo3-component-border-radius, 4px);
      overflow: hidden;
    }

    /* ── Header ── */
    .review-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0.75rem 1rem;
      background: var(--typo3-component-bg-dark, #1a1a1a);
      color: #fff;
      gap: 1rem;
    }

    .review-header-check {
      font-weight: 600;
      font-size: 0.9375rem;
      flex: 1;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .review-step-badge {
      font-size: 0.8125rem;
      background: var(--assist-review-accent);
      color: #fff;
      padding: 0.2rem 0.6rem;
      border-radius: 2rem;
      white-space: nowrap;
      flex-shrink: 0;
    }

    /* ── Subheader ── */
    .review-subheader {
      padding: 0.5rem 1rem;
      background: var(--assist-review-step-indicator-bg);
      border-bottom: 1px solid var(--typo3-component-border-color, #dee2e6);
      font-size: 0.8125rem;
      color: var(--typo3-muted-color, #6c757d);
      display: flex;
      gap: 0.5rem;
      flex-wrap: wrap;
    }

    .review-page-name {
      font-weight: 600;
      color: var(--typo3-component-color, #333);
    }

    /* ── Items ── */
    .items-list {
      display: flex;
      flex-direction: column;
      gap: 0;
    }

    .review-item {
      padding: 1rem;
      border-bottom: 1px solid var(--typo3-component-border-color, #dee2e6);
    }

    .review-item:last-child {
      border-bottom: none;
    }

    .item-header {
      display: flex;
      align-items: flex-start;
      gap: 0.75rem;
      margin-bottom: 0.75rem;
    }

    .item-thumbnail {
      width: 72px;
      height: 56px;
      object-fit: cover;
      border-radius: 2px;
      border: 1px solid var(--typo3-component-border-color, #dee2e6);
      flex-shrink: 0;
      background: var(--typo3-component-bg, #f8f9fa);
    }

    .item-thumbnail-placeholder {
      width: 72px;
      height: 56px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 2px;
      border: 1px solid var(--typo3-component-border-color, #dee2e6);
      background: var(--typo3-component-bg, #f8f9fa);
      flex-shrink: 0;
      color: var(--typo3-muted-color, #adb5bd);
    }

    .item-meta {
      flex: 1;
      min-width: 0;
    }

    .item-filename {
      font-weight: 600;
      font-size: 0.875rem;
      color: var(--typo3-component-color, #333);
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .item-current-alt {
      font-size: 0.8125rem;
      color: var(--typo3-muted-color, #6c757d);
      margin-top: 0.25rem;
    }

    .item-current-alt-value {
      display: inline-block;
      background: var(--typo3-component-bg, #f8f9fa);
      border: 1px solid var(--typo3-component-border-color, #dee2e6);
      border-radius: 2px;
      padding: 0.1rem 0.375rem;
      font-family: monospace;
      font-size: 0.8125rem;
      max-width: 100%;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
      vertical-align: middle;
    }

    /* Assessment badge */
    .item-assessment {
      display: flex;
      align-items: flex-start;
      gap: 0.5rem;
      padding: 0.5rem 0.625rem;
      border-radius: 3px;
      font-size: 0.8125rem;
      margin-bottom: 0.75rem;
      border-left: 3px solid transparent;
    }

    .item-assessment.severity-warning {
      background: var(--assist-review-warning-bg);
      border-color: var(--assist-review-warning-border);
      color: #856404;
    }

    .item-assessment.severity-error {
      background: var(--assist-review-error-bg);
      border-color: var(--assist-review-error-border);
      color: #842029;
    }

    .item-assessment.severity-ok {
      background: var(--assist-review-ok-bg);
      border-color: var(--assist-review-ok-border);
      color: #155724;
    }

    .item-assessment-icon {
      flex-shrink: 0;
    }

    /* Suggestions */
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
      margin: 0 0 0.5rem;
      padding: 0;
      display: flex;
      flex-direction: column;
      gap: 0.25rem;
    }

    .suggestion-item {
      display: flex;
      align-items: flex-start;
      gap: 0.5rem;
      padding: 0.375rem 0.625rem;
      border: 1px solid var(--typo3-component-border-color, #dee2e6);
      border-radius: 3px;
      cursor: pointer;
    }

    .suggestion-item:has(input:checked) {
      border-color: var(--assist-review-accent);
      background: color-mix(in srgb, var(--assist-review-accent) 6%, transparent);
    }

    .suggestion-item input[type="radio"] {
      margin-top: 0.2rem;
      flex-shrink: 0;
      accent-color: var(--assist-review-accent);
    }

    .suggestion-text {
      font-size: 0.875rem;
      line-height: 1.5;
    }

    .item-actions {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      flex-wrap: wrap;
      margin-top: 0.5rem;
    }

    .item-disclaimer {
      font-size: 0.75rem;
      color: var(--typo3-muted-color, #6c757d);
      font-style: italic;
      flex: 1;
    }

    .loading-wrapper {
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 1rem;
    }

    /* ── Footer ── */
    .review-footer {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0.75rem 1rem;
      background: var(--assist-review-step-indicator-bg);
      border-top: 1px solid var(--typo3-component-border-color, #dee2e6);
      gap: 1rem;
    }

    .footer-next-check {
      font-size: 0.8125rem;
      color: var(--typo3-muted-color, #6c757d);
      display: flex;
      align-items: center;
      gap: 0.375rem;
      flex: 1;
      justify-content: center;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    /* Buttons */
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
      white-space: nowrap;
    }

    .btn-sm {
      padding: 0.2rem 0.5rem;
      font-size: 0.75rem;
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
      background: var(--assist-review-accent);
      color: #fff;
      border-color: var(--assist-review-accent);
    }

    .btn-primary:hover:not(:disabled) {
      opacity: 0.9;
    }

    .btn-primary:disabled {
      opacity: 0.5;
      cursor: not-allowed;
    }
  `;

  @property({ type: String }) page: string = '';
  @property({ type: String }) check: string = '';
  @property({ type: Number }) step: number = 1;
  @property({ type: Number, attribute: 'total-steps' }) totalSteps: number = 1;
  @property({ type: String, attribute: 'next-check' }) nextCheck: string = '';
  @property({ type: Array }) items: ReviewItem[] = [];

  @state() private selections: Record<string, number> = {};
  @state() private loadingItems: Set<string> = new Set();

  protected override render(): TemplateResult {
    const nextCheckEl = this.nextCheck ? html`
      <span class="footer-next-check">
        <typo3-backend-icon identifier="actions-arrow-right" size="small"></typo3-backend-icon>
        ${this.nextCheck}
      </span>
    ` : nothing;

    return html`
      <div class="review-wrapper">
        <div class="review-header">
          <span class="review-header-check">${this.check}</span>
          <span class="review-step-badge">Step ${this.step} of ${this.totalSteps}</span>
        </div>

        <div class="review-subheader">
          <span>Reviewing accessibility for Page:</span>
          <span class="review-page-name">${this.page}</span>
          <span>— This review is based on: content found on the page.</span>
        </div>

        <div class="items-list">
          ${this.items.map(item => this.renderItem(item))}
        </div>

        <div class="review-footer">
          <button
            type="button"
            class="btn btn-default"
            @click=${this.handlePrevious}
            ?disabled=${this.step <= 1}
          >
            <typo3-backend-icon identifier="actions-arrow-left" size="small"></typo3-backend-icon>
            Previous
          </button>

          ${nextCheckEl}

          <button
            type="button"
            class="btn btn-primary"
            @click=${this.handleContinue}
          >
            ${this.step >= this.totalSteps ? 'Finish' : 'Continue'}
            ${this.step < this.totalSteps ? html`<typo3-backend-icon identifier="actions-arrow-right" size="small"></typo3-backend-icon>` : nothing}
          </button>
        </div>
      </div>
    `;
  }

  private getSelectedIndex(itemId: string): number {
    return this.selections[itemId] ?? 0;
  }

  private setSelection(itemId: string, index: number): void {
    this.selections = { ...this.selections, [itemId]: index };
  }

  private handlePrevious(): void {
    this.dispatchEvent(new CustomEvent('assist:previous', { bubbles: true, composed: true }));
  }

  private handleContinue(): void {
    const result: Record<string, string> = {};
    for (const item of this.items) {
      const idx = this.getSelectedIndex(item.id);
      result[item.id] = item.suggestions[idx] ?? '';
    }
    this.dispatchEvent(new CustomEvent('assist:continue', {
      bubbles: true,
      composed: true,
      detail: { selections: result },
    }));
  }

  private handleReplaceItem(item: ReviewItem): void {
    const idx = this.getSelectedIndex(item.id);
    const value = item.suggestions[idx] ?? '';
    this.dispatchEvent(new CustomEvent('assist:replace-item', {
      bubbles: true,
      composed: true,
      detail: { id: item.id, value },
    }));
  }

  private handleRegenerateItem(item: ReviewItem): void {
    this.loadingItems = new Set([...this.loadingItems, item.id]);
    this.dispatchEvent(new CustomEvent('assist:regenerate-item', {
      bubbles: true,
      composed: true,
      detail: { id: item.id },
    }));
  }

  private getAssessmentIcon(severity: ReviewItem['assessmentSeverity']): string {
    switch (severity) {
      case 'error': return 'status-dialog-error';
      case 'warning': return 'status-dialog-warning';
      case 'ok': return 'status-dialog-ok';
      default: return 'status-dialog-information';
    }
  }

  private renderItem(item: ReviewItem): TemplateResult {
    const isLoading = this.loadingItems.has(item.id);
    const selectedIdx = this.getSelectedIndex(item.id);
    const assessmentClasses = classMap({
      'item-assessment': true,
      [`severity-${item.assessmentSeverity}`]: true,
    });
    const thumbnail = item.thumbnail
      ? html`<img class="item-thumbnail" src=${item.thumbnail} alt="" aria-hidden="true" />`
      : html`<div class="item-thumbnail-placeholder" aria-hidden="true"><typo3-backend-icon identifier="mimetypes-media-image" size="small"></typo3-backend-icon></div>`;

    return html`
      <div class="review-item">
        <div class="item-header">
          ${thumbnail}
          <div class="item-meta">
            <div class="item-filename">${item.filename}</div>
            <div class="item-current-alt">
              Current alt text:
              <span class="item-current-alt-value">${item.currentAlt || '(empty)'}</span>
            </div>
          </div>
        </div>

        <div class=${assessmentClasses}>
          <span class="item-assessment-icon">
            <typo3-backend-icon identifier=${this.getAssessmentIcon(item.assessmentSeverity)} size="small"></typo3-backend-icon>
          </span>
          <span>${item.assessment}</span>
        </div>

        ${isLoading ? this.renderItemLoading() : this.renderItemSuggestions(item, selectedIdx)}
      </div>
    `;
  }

  private renderItemLoading(): TemplateResult {
    return html`
      <div class="loading-wrapper" aria-busy="true">
        <typo3-backend-spinner size="small"></typo3-backend-spinner>
      </div>
    `;
  }

  private renderItemSuggestions(item: ReviewItem, selectedIdx: number): TemplateResult {
    return html`
      <div class="suggestions-label">Suggested alt text</div>
      <ul class="suggestions-list" role="radiogroup" aria-label="Suggested alt texts for ${item.filename}">
        ${item.suggestions.map((suggestion, index) => html`
          <li
            class="suggestion-item"
            @click=${() => this.setSelection(item.id, index)}
          >
            <input
              type="radio"
              name="suggestion-${item.id}"
              .checked=${selectedIdx === index}
              @change=${() => this.setSelection(item.id, index)}
              aria-label=${suggestion}
            />
            <span class="suggestion-text">${suggestion}</span>
          </li>
        `)}
      </ul>

      <div class="item-actions">
        <span class="item-disclaimer">AI may be wrong.</span>
        <button
          type="button"
          class="btn btn-sm btn-default"
          @click=${() => this.handleRegenerateItem(item)}
        >
          <typo3-backend-icon identifier="actions-refresh" size="small"></typo3-backend-icon>
          Regenerate
        </button>
        <button
          type="button"
          class="btn btn-sm btn-primary"
          ?disabled=${item.suggestions.length === 0}
          @click=${() => this.handleReplaceItem(item)}
        >
          Replace field
        </button>
      </div>
    `;
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-assist-a11y-review': A11yReviewElement;
  }
}
