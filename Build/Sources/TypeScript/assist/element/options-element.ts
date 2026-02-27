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
  identifier: string;
  text: string;
  details?: string | null;
}

const demoPath: string = '/typo3/sysext/assist/Resources/Public/Demo/';
const imagePlaceholderA: string = demoPath + 'banner_ultrawide.jpg';
const imagePlaceholderB: string = demoPath + 'detail_stress.jpg';
const imagePlaceholderC: string = demoPath + 'photo_sim.jpg';
// const videoPlaceholderA: string = demoPath + 'moving_test.mp4';
// const videoPlaceholderB: string = demoPath + 'moving_test.mp4';

/**
 * Module: @typo3/assist/element/options-element
 */
@customElement('typo3-assist-options-element')
export class OptionsElement extends LitElement {
  @property({ type: String, reflect: true }) type = 'list';
  @property({ type: String }) key: string = '';
  @property({ type: String }) text: string = '';
  @property({ type: Array }) options: OptionItem[] = [];

  override createRenderRoot(): HTMLElement | DocumentFragment {
    return this;
  }

  protected override render(): TemplateResult {
    if (this.type === 'panel') {
      return this.renderPanelType();
    }
    return this.renderListType();
  }

  private renderListType(): TemplateResult {
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
              <button type="button" class="assist-chat__option-action btn btn-default" @click=${() => this.handleAccept(option)}>${labels.get('button.accept')}</button>
            </div>
          </article>
        `)}
      </div>
    `;
  }

  private renderPanelType(): TemplateResult {
    // @todo hardcoded - fill in the blanks
    return html`
      <p class="assist-chat__text">
        I generated image suggestions for this page. Please choose the ones you want to use.
      </p>

      <div class="row g-3 assist-chat__media-row assist-chat__media-row--images">
        <div class="col-12 col-md-6 col-xl-4">
          <article class="panel panel-default assist-chat__option assist-chat__media-panel">
            <div class="panel-heading">
              <h3 class="h5 assist-chat__option-title">Team meeting in modern office</h3>
            </div>
            <div class="panel-body assist-chat__option-text">
              <div class="assist-chat__media-frame">
                <img class="assist-chat__media-thumbnail" src="${imagePlaceholderA}" alt="Banner ultrawide" />
              </div>
            </div>
            <div class="panel-footer assist-chat__option-actions">
              <button type="button" class="assist-chat__option-action btn btn-default">${labels.get('button.insert')}</button>
              <button
                type="button"
                class="assist-chat__option-action btn btn-default"
              >
                <typo3-backend-icon identifier="actions-eye"></typo3-backend-icon>
              </button>
            </div>
          </article>
        </div>

        <div class="col-12 col-md-6 col-xl-4">
          <article class="panel panel-default assist-chat__option assist-chat__media-panel">
            <div class="panel-heading">
              <h3 class="h5 assist-chat__option-title">Person working on laptop with analytics dashboard</h3>
            </div>
            <div class="panel-body assist-chat__option-text">
              <div class="assist-chat__media-frame">
                <img class="assist-chat__media-thumbnail" src="${imagePlaceholderB}" alt="Detail stress" />
              </div>
            </div>
            <div class="panel-footer assist-chat__option-actions">
              <button type="button" class="assist-chat__option-action btn btn-default">${labels.get('button.insert')}</button>
              <button
                type="button"
                class="assist-chat__option-action btn btn-default"
              >
                <typo3-backend-icon identifier="actions-eye"></typo3-backend-icon>
              </button>
            </div>
          </article>
        </div>

        <div class="col-12 col-md-6 col-xl-4">
          <article class="panel panel-default assist-chat__option assist-chat__media-panel">
            <div class="panel-heading">
              <h3 class="h5 assist-chat__option-title">Close-up of hands typing on keyboard</h3>
            </div>
            <div class="panel-body assist-chat__option-text">
              <div class="assist-chat__media-frame">
                <img class="assist-chat__media-thumbnail" src="${imagePlaceholderC}" alt="Photo sim" />
              </div>
            </div>
            <div class="panel-footer assist-chat__option-actions">
              <button type="button" class="assist-chat__option-action btn btn-default">${labels.get('button.insert')}</button>
              <button
                type="button"
                class="assist-chat__option-action btn btn-default"
              >
                <typo3-backend-icon identifier="actions-eye"></typo3-backend-icon>
              </button>
            </div>
          </article>
        </div>
      </div>

      <p class="assist-chat__text">
        You can select one or multiple images. I will insert them into suitable positions in the content.
      </p>
    `;
    /*
            <div class="row g-3 assist-chat__media-row assist-chat__media-row--videos">
              <div class="col-12 col-lg-6">
                <article class="panel panel-default assist-chat__option assist-chat__media-panel">
                  <div class="panel-heading">
                    <h3 class="h5 assist-chat__option-title">Video 1</h3>
                  </div>
                  <div class="panel-body assist-chat__option-text">
                    <div class="assist-chat__media-frame assist-chat__media-frame--video">
                      <video class="assist-chat__media-video" src="${this.videoPlaceholderA}" controls></video>
                    </div>
                  </div>
                  <div class="panel-footer assist-chat__option-actions">
                    <button type="button" class="assist-chat__option-action btn btn-default">${labels.get('button.insert')}</button>
                  </div>
                </article>
              </div>

              <div class="col-12 col-lg-6">
                <article class="panel panel-default assist-chat__option assist-chat__media-panel">
                  <div class="panel-heading">
                    <h3 class="h5 assist-chat__option-title">Video 2</h3>
                  </div>
                  <div class="panel-body assist-chat__option-text">
                    <div class="assist-chat__media-frame assist-chat__media-frame--video">
                      <video class="assist-chat__media-video" src="${this.videoPlaceholderB}" controls></video>
                    </div>
                  </div>
                  <div class="panel-footer assist-chat__option-actions">
                    <button type="button" class="assist-chat__option-action btn btn-default">${labels.get('button.insert')}</button>
                  </div>
                </article>
              </div>
            </div>
          </div>
     */
  }

  private handleAccept(option: OptionItem): void {
    this.dispatchEvent(new CustomEvent<{ key: string; identifier: string; text: string }>('typo3-assist-option-accept', {
      detail: { key: this.key, identifier: option.identifier, text: option.text },
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
