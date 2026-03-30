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
import { markdown } from '@typo3/core/directive/markdown';
// import { LabelProvider } from '@typo3/backend/localization/label-provider';
import labels from '~labels/assist.elements';

export interface OptionItemData {
  identifier: string;
  text: string;
  details?: string | null;
  src?: string | null;
}

export interface OptionsFeedbackItem {
  type: 'options';
  key: string;
  text: string;
  options: OptionItemData[];
  view?: 'list' | 'image' | 'video';
  image?: string | null;
  video?: string | null;
  heading?: string | null;
}

/**
 * Module: @typo3/assist/element/options-element
 */
@customElement('typo3-assist-options-element')
export class OptionsElement extends LitElement {
  @property({ type: String, reflect: true }) type = 'list';
  @property({ type: String }) key: string = '';
  @property({ type: String }) text: string = '';
  @property({ type: Array }) items: OptionItemData[] = [];
  @property({ type: String }) heading: string | null = null;
  @property({ type: String }) image: string | null = null;
  @property({ type: String }) video: string | null = null;

  @state() private selectedIdentifier: string | null = null;

  override createRenderRoot(): HTMLElement | DocumentFragment {
    return this;
  }

  protected override render(): TemplateResult {
    let content: TemplateResult;
    switch (this.type) {
      case 'image': content = this.renderImages(); break;
      case 'video': content = this.renderVideos(); break;
      default: content = this.renderListItems(); break;
    }
    return html`
      <article class="panel panel-default assist-chat__option assist-chat__media-panel">
        ${this.renderHeading()}
        <div class="panel-body assist-chat__option-text">
            ${this.renderMediaPreview()}
            ${content}
        </div>
      </article>
    `;
  }

  private renderHeading(): TemplateResult {
    if (this.heading) {
      return html`
        <div class="panel-heading">
          <h3 class="h5 assist-chat__options-heading">${this.heading}</h3>
        </div>
      `;
    }
    return html`${nothing}`;
  }

  private renderMediaPreview(): TemplateResult {
    const kind = this.image !== null ? 'image' : this.video !== null ? 'video' : null;
    let content: TemplateResult;
    switch (kind) {
      case 'image': content = html`
          <div class="assist-chat__media-frame">
            <img class="assist-chat__media-thumbnail" src=${this.image!} alt="">
          </div>
        `;
        break;
      case 'video': content = html`
          <div class="assist-chat__media-frame assist-chat__media-frame--video">
            <video class="assist-chat__media-video" src=${this.video!} controls></video>
          </div>
        `;
        break;
      default: content = html`${nothing}`;
    }
    return html`
      <div class="assist-chat__alt-media">
        ${content}
      </div>
    `;
  }

  private renderListItems(): TemplateResult {
    return html`
      <div class="assist-chat__alt-text">
        <p class="assist-chat__option-alt-text">${markdown(this.text, 'minimal')}</p>
        <div class="assist-chat__options">
          ${this.items.map((item, index) => html`
            <article class="panel panel-default assist-chat__option">
              <div class="panel-heading">
                <h3 class="h5 assist-chat__option-title">Option ${this.indexToChar(index)} - ${item.text}</h3>
              </div>
              ${item.details ? html`
                <div class="panel-body assist-chat__option-text">${item.details}</div>
              ` : nothing}
              <div class="panel-footer assist-chat__option-actions">
                <button type="button" class="assist-chat__option-action btn btn-default" ?disabled=${this.selectedIdentifier !== null} @click=${() => this.handleSelection(item)}>${labels.get('button.accept')}</button>
              </div>
            </article>
          `)}
        </div>
      </div>
    `;
  }

  private renderImages(): TemplateResult {
    return html`
      <p class="assist-chat__text">${markdown(this.text, 'minimal')}</p>
      <div class="row g-3 assist-chat__media-row assist-chat__media-row--images">
        ${this.items.map(item => html`
          <div class="col-12 col-md-6 col-xl-4">
            <article class="panel panel-default assist-chat__option assist-chat__media-panel">
              <div class="panel-heading">
                <h3 class="h5 assist-chat__option-title">${item.text}</h3>
              </div>
              <div class="panel-body assist-chat__option-text">
                <div class="assist-chat__media-frame">
                  <img class="assist-chat__media-thumbnail" src="${item.src ?? ''}" alt="${item.text}"/>
                </div>
              </div>
              <div class="panel-footer assist-chat__option-actions">
                <button type="button" class="assist-chat__option-action btn btn-default" ?disabled=${this.selectedIdentifier !== null} @click=${() => this.handleSelection(item)}>${labels.get('button.insert')}</button>
                <button type="button" class="assist-chat__option-action btn btn-default" ?disabled=${this.selectedIdentifier !== null}>
                  <typo3-backend-icon identifier="actions-eye"></typo3-backend-icon>
                </button>
              </div>
            </article>
          </div>
        `)}
      </div>
    `;
  }

  private renderVideos(): TemplateResult {
    return html`
      <p class="assist-chat__text">${markdown(this.text, 'minimal')}</p>
      <div class="row g-3 assist-chat__media-row assist-chat__media-row--videos">
        ${this.items.map(item => html`
          <div class="col-12 col-lg-6">
            <article class="panel panel-default assist-chat__option assist-chat__media-panel">
              <div class="panel-heading">
                <h3 class="h5 assist-chat__option-title">${item.text}</h3>
              </div>
              <div class="panel-body assist-chat__option-text">
                <div class="assist-chat__media-frame assist-chat__media-frame--video">
                  <video class="assist-chat__media-video" src="${item.src ?? ''}" controls></video>
                </div>
              </div>
              <div class="panel-footer assist-chat__option-actions">
                <button type="button" class="assist-chat__option-action btn btn-default" ?disabled=${this.selectedIdentifier !== null} @click=${() => this.handleSelection(item)}>${labels.get('button.insert')}</button>
              </div>
            </article>
          </div>
        `)}
      </div>
    `;
  }

  private handleSelection(item: OptionItemData): void {
    this.selectedIdentifier = item.identifier;
    const recover = () => { this.selectedIdentifier = null; };
    this.dispatchEvent(new CustomEvent<{ key: string; identifier: string; text: string; recover: () => void }>('typo3-assist-option-select', {
      detail: { key: this.key, identifier: item.identifier, text: item.text, recover },
      bubbles: true,
    }));
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
