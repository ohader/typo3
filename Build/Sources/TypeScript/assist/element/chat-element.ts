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
import { LabelProvider } from '@typo3/backend/localization/label-provider';
import labels from '~labels/assist.elements';

import '@typo3/assist/element/options-element';

export interface AssistChatProperties {
  module: string,
  subject: string;
  assistant: string;
  labelDomain: string;
}

interface AssistChatStep {
  identifier: string;
  description: string;
  subject: string;
  subs: AssistChatStep[];
  done: boolean;
}

/**
 * Module: @typo3/assist/element/chat-element
 */
@customElement('typo3-assist-chat-element')
export class ChatElement extends LitElement {
  // @todo remove `template` occurrences
  @property({ type: String, reflect: true }) template: string = 'meta';

  @property({ type: String, reflect: true }) module: string;
  @property({ type: String, reflect: true }) subject: string;
  @property({ type: String, reflect: true }) assistant: string;
  @property({ type: Object }) labels: LabelProvider<any>;

  @state() steps: AssistChatStep[] = [];

  private readonly mediaBasePath: string = '/typo3/sysext/assist/Resources/Public/Demo/';
  private readonly imagePlaceholderA: string = this.mediaBasePath + 'banner_ultrawide.jpg';
  private readonly imagePlaceholderB: string = this.mediaBasePath + 'detail_stress.jpg';
  private readonly imagePlaceholderC: string = this.mediaBasePath + 'photo_sim.jpg';
  private readonly videoPlaceholderA: string = this.mediaBasePath + 'moving_test.mp4';
  private readonly videoPlaceholderB: string = this.mediaBasePath + 'moving_test.mp4';

  override createRenderRoot(): HTMLElement | DocumentFragment {
    return this;
  }

  protected override firstUpdated(): void {
    this.scrollToBottom();
    this.closest('typo3-backend-modal')?.addEventListener('typo3-modal-shown', this.handleModalShown, { once: true });
  }

  protected override render(): TemplateResult {
    const template = this.resolveTemplate();

    const optionsA = [
      { text: 'neutral / informative', details: 'Discover current spring trends, styling ideas, and practical tips for updating your wardrobe. Learn what to wear this season and how to combine outfits effortlessly.' },
      { text: 'more SEO/keyword-focused', details: 'Explore the Spring 2025 fashion trends, outfit ideas, and styling tips. Find inspiration for modern looks and build a versatile wardrobe for the new season.' },
      { text: 'more marketing/click-oriented', details: 'Refresh your wardrobe this spring. Get outfit inspiration, trending colors, and easy styling tips to create modern looks for work, leisure, and everyday wear.' }
    ];

    return html`
      <div class="assist-chat-container">
        <div class="assist-chat-header">
          <div class="assist-chat-header__info">
            <h2 class="h4 assist-chat-header__title">
              <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M6.758.254c-.29.25-.495.54-.495 1.409 0 2.364 2.984 9.464 5.017 9.464a2.2 2.2 0 0 0 .502-.053l.171-.048C10.17 13.886 8.003 16 6.718 16l-.132-.006C3.816 15.739 0 7.569 0 3.938c0-.507.095-.919.257-1.208l.073-.116C1.285 1.454 4.27.544 6.758.254ZM1.5 3.938c0 .7.196 1.747.589 2.989.385 1.218.93 2.53 1.557 3.732.632 1.212 1.318 2.255 1.963 2.97.323.358.602.596.824.736a.963.963 0 0 0 .275.132c.03-.008.145-.04.356-.164.283-.167.638-.44 1.046-.831.44-.422.907-.949 1.377-1.556a6.244 6.244 0 0 1-1.017-1.024c-.665-.826-1.286-1.898-1.812-2.988a25.121 25.121 0 0 1-1.33-3.348c-.263-.845-.473-1.711-.54-2.451-.46.113-.91.244-1.327.39-1.04.36-1.657.732-1.907.974-.004.01-.009.024-.013.04a1.655 1.655 0 0 0-.041.4ZM11.133.009c2.4.05 4.661.5 4.662 1.86l-.006.277c-.11 2.886-1.89 6.23-2.814 6.23l-.16-.013c-1.615-.27-3.526-4.502-3.647-6.85l-.006-.228C9.162.205 9.577 0 10.652 0l.482.009Zm-.462 1.495c.061.883.47 2.282 1.101 3.54.335.668.687 1.203 1 1.547l.032.034c.177-.254.38-.602.579-1.031.496-1.067.872-2.393.908-3.548a2.352 2.352 0 0 0-.53-.215c-.753-.222-1.86-.326-3.09-.327Z"/></svg>
              <span class="assist-chat-header__title-text">${this.labels.get('chat.title')}</span>
            </h2>
            ${this.renderSubjectContext()}
          </div>
          <div class="assist-chat-header__buttons">
            <button type="button" class="assist-chat-header__button btn btn-default" disabled>
              <typo3-backend-icon identifier="actions-history"></typo3-backend-icon>
            </button>
            <button
              type="button"
              class="assist-chat-header__button btn btn-default"
              @click=${this.handleCloseClick}
            >
              Close
              <typo3-backend-icon identifier="actions-close"></typo3-backend-icon>
            </button>
          </div>
          ${this.renderSteps()}
        </div>
        <div class="assist-chat">

          <!-- Generate Meta Description -->
          ${template === 'meta' ? html`
          <div class="assist-chat__response">
            <ul class="assist-chat__quick-actions">
              <li><a href="#" class="assist-chat__quick-action">Improve readability</a></li>
              <li><a href="#" class="assist-chat__quick-action">Suggest internal links</a></li>
            </ul>
          </div>

          <div class="assist-chat__user-input">
            <p class="assist-chat__user-input-bubble">Improve meta description</p>
          </div>

          <div class="assist-chat__response">
            <p class="assist-chat__text">
              The current meta description is either missing or too generic. Search engines will likely rewrite it, which can reduce click-through rate.
            </p>

            <typo3-assist-options-element
              text="I created a few alternative meta descriptions you can choose from:"
              .options=${optionsA}
            ></typo3-assist-options-element>

            <p class="assist-chat__text">
              You can modify the description directly.<br />
              Tell me what you want to change (length, tone, keywords, audience).
            </p>
          </div>

          <div class="assist-chat__user-input">
            <p class="assist-chat__user-input-bubble">
              make it more about sustainable materials and less fashion magazine style
            </p>
          </div>

          <div class="assist-chat__response">
            <p class="assist-chat__text">
              Understood. I adjusted the description to emphasize sustainability and reduced the editorial tone.
            </p>

            <div class="assist-chat__options">
              <article class="panel panel-default assist-chat__option">
                <div class="panel-body assist-chat__option-text">
                  Discover spring outfits made with sustainable materials and environmentally conscious production. Learn how to update your wardrobe with durable, modern clothing choices for everyday wear.
                </div>
                <div class="panel-footer assist-chat__option-actions">
                  <button type="button" class="assist-chat__option-action btn btn-default">${labels.get('button.insert')}</button>
                </div>
              </article>
            </div>
          </div>

          ${this.renderThinking()}

          ${this.renderInput()}
          ` : ''}

          <!-- Generate Media -->
          ${template === 'media' ? html`
          <div class="assist-chat__user-input">
            <p class="assist-chat__user-input-bubble">Generate media</p>
          </div>

          <div class="assist-chat__response">
            <p class="assist-chat__text">
              I can help you enrich this page with media. I’ll look for suitable assets based on your content.
            </p>

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
                      <img class="assist-chat__media-thumbnail" src="${this.imagePlaceholderA}" alt="Banner ultrawide" />
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
                      <img class="assist-chat__media-thumbnail" src="${this.imagePlaceholderB}" alt="Detail stress" />
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
                      <img class="assist-chat__media-thumbnail" src="${this.imagePlaceholderC}" alt="Photo sim" />
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
          </div>

          <div class="assist-chat__user-input">
            <p class="assist-chat__user-input-bubble">I would rather use videos.</p>
          </div>

          <div class="assist-chat__response">
            <p class="assist-chat__text">
              No problem — I’ll look for suitable video material instead.
            </p>

            <p class="assist-chat__text">
              I found videos that match the topic of this page.
            </p>

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

          ${this.renderInput()}
          ` : ''}

          <!-- Generate Image Alternative Text -->
          ${template === 'alt' ? html`
          <div class="assist-chat__user-input">
            <p class="assist-chat__user-input-bubble">Generate image alternative text</p>
          </div>

          <div class="assist-chat__response">
            <p class="assist-chat__text">
              Please review the generated alternative texts.
            </p>

            <p class="assist-chat__text">
              Image 1 of 51
            </p>

            <article class="panel panel-default assist-chat__option assist-chat__media-panel">
              <div class="panel-heading">
                <h3 class="h5 assist-chat__option-title">imagename.jpg</h3>
              </div>
              <div class="panel-body assist-chat__option-text">
                <div class="assist-chat__alt-media">
                  <div class="assist-chat__media-frame">
                    <img class="assist-chat__media-thumbnail" src="${this.imagePlaceholderC}" alt="Banner ultrawide" />
                  </div>
                </div>
                <div class="assist-chat__alt-text">
                  <p class="assist-chat__option-alt-text">
                    Current alt text:<br>
                    "A person sitting at a desk working on a laptop, with a cup of coffee and a plant next to them."
                  </p>
                  <div class="assist-chat__options">
                    <article class="panel panel-default assist-chat__option">
                      <div class="panel-body assist-chat__option-text">
                        A person working on a laptop at a desk
                      </div>
                      <div class="panel-footer assist-chat__option-actions">
                        <button type="button" class="assist-chat__option-action btn btn-default">Replace</button>
                      </div>
                    </article>

                    <article class="panel panel-default assist-chat__option">
                      <div class="panel-body assist-chat__option-text">
                        Office workspace with laptop and notebook
                      </div>
                      <div class="panel-footer assist-chat__option-actions">
                        <button type="button" class="assist-chat__option-action btn btn-default">Replace</button>
                      </div>
                    </article>

                    <article class="panel panel-default assist-chat__option">
                      <div class="panel-body assist-chat__option-text">
                        Close view of hands typing on a keyboard
                      </div>
                      <div class="panel-footer assist-chat__option-actions">
                        <button type="button" class="assist-chat__option-action btn btn-default">Replace</button>
                      </div>
                    </article>
                  </div>

                </div>
              </div>
            </article>
          </div>

          ${this.renderInput()}
          ` : ''}

        </div>
      </div>
    `;
  }

  private scrollToBottom(): void {
    requestAnimationFrame((): void => {
      const chatBody = this.querySelector<HTMLElement>('.assist-chat');
      if (!chatBody) {
        return;
      }
      chatBody.scrollTop = chatBody.scrollHeight;
    });
  }

  private readonly handleModalShown = (): void => {
    this.scrollToBottom();
  };

  private handleCloseClick(): void {
    this.closest('typo3-backend-modal')?.hideModal();
  }

  private renderSubjectContext(): TemplateResult {
    return html`
      <p class="assist-chat-header__context text-variant">
        ${this.subject}
      </p>
    `;
  }

  /**
   * @todo probably make it an own component
   */
  private renderSteps(): TemplateResult {
    if (this.steps.length === 0) {
      return html`${nothing}`;
    }
    const stages = this.resolveStepIdentifiers(this.steps);
    const activeStage = this.countDoneSteps(this.steps);
    return html`
      <div class="assist-chat-header__progress">
        <typo3-backend-progress-tracker
          activeStage=${activeStage}
          .stages=${stages}>
        </typo3-backend-progress-tracker>
      </div>
    `;
  }

  private resolveStepIdentifiers(steps: AssistChatStep[]): string[] {
    return steps.flatMap(step => [step.identifier, ...this.resolveStepIdentifiers(step.subs)]);
  }

  private countDoneSteps(steps: AssistChatStep[]): number {
    return steps.reduce((count, step) => count + (step.done ? 1 : 0) + this.countDoneSteps(step.subs), 0);
  }

  /**
   * @todo make this a separate component `<typo3-assist-thinking-element>`
   */
  private renderThinking(): TemplateResult {
    return html`
      <div class="assist-chat__thinking">
        <svg class="assist-chat__thinking-spinner" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
          <circle class="assist-chat__thinking-dot assist-chat__thinking-dot--1" cx="12" cy="12" r="11" />
          <circle class="assist-chat__thinking-dot assist-chat__thinking-dot--2" cx="12" cy="12" r="11" />
          <circle class="assist-chat__thinking-dot assist-chat__thinking-dot--3" cx="12" cy="12" r="11" />
        </svg>
        <p class="assist-chat__thinking-text">Thinking...</p>
      </div>
    `;
  }

  private renderInput(): TemplateResult {
    return html`
      <div class="assist-chat__input">
        <input type="text" class="form-control" id="inputFormControlPlaceholder" placeholder="Tell me what you want to change about the meta description…" autofocus>
        <button type="button" class="btn btn-primary assist-chat__input-button">
          <typo3-backend-icon identifier="actions-arrow-up-alt"></typo3-backend-icon>
        </button>
      </div>
    `;
  }

  private resolveTemplate(): 'meta' | 'media' | 'alt' {
    if (this.template === 'media' || this.template === 'alt') {
      return this.template;
    }
    return 'meta';
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-assist-chat-element': ChatElement;
  }
}
