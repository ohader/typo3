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
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import { markdown } from '@typo3/core/directive/markdown';

import '@typo3/assist/element/list-element';
import '@typo3/assist/element/options-element';
import '@typo3/assist/element/quick-actions-element';

import type { ListFeedbackItem } from '@typo3/assist/element/list-element';
import type { OptionsFeedbackItem } from '@typo3/assist/element/options-element';
import type { QuickActionsFeedbackItem } from '@typo3/assist/element/quick-actions-element';

export interface AssistChatProperties {
  additionalModule?: string;
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

interface TextFeedbackItem {
  type: 'text';
  text: string;
}

interface MarkdownFeedbackItem {
  type: 'markdown';
  text: string;
}

interface ConfirmationFeedbackItem {
  type: 'confirmation';
  text: string;
  acceptLabel: string;
  declineLabel: string;
}

type AssistFeedbackItem = TextFeedbackItem | MarkdownFeedbackItem | ConfirmationFeedbackItem | OptionsFeedbackItem | ListFeedbackItem | QuickActionsFeedbackItem;

type ChatEntry =
  | { kind: 'user'; text: string }
  | { kind: 'assistant'; item: AssistFeedbackItem }
  | { kind: 'error'; text: string };

interface AssistantServerResponse {
  feedback: AssistFeedbackItem[];
  steps: AssistChatStep[];
  progress: { uuid: string } | null;
  error?: string;
}

interface ResourceSubjectData {
  kind: 'resource';
  type: 'file' | 'folder';
  storage: number;
  path: string;
}

interface TcaSubjectData {
  kind: 'tca';
  tableName: string;
  uid: number;
  propertyName: string;
  flexFormPath: string[] | null;
  types: string[] | null;
}

type SubjectData = ResourceSubjectData | TcaSubjectData;

/**
 * Module: @typo3/assist/element/chat-element
 */
@customElement('typo3-assist-chat-element')
export class ChatElement extends LitElement {
  // @todo remove `template` occurrences
  @property({ type: String, reflect: true }) template: string = 'meta';

  @property({ type: String, reflect: true }) subject: string;
  @property({ type: String, reflect: true }) assistant: string;
  @property({ type: Object }) labels: LabelProvider<any>;

  @state() steps: AssistChatStep[] = [];
  @state() isLoading: boolean = false;
  @state() private progressUuid: string | null = null;
  @state() private messages: ChatEntry[] = [];

  private historyIndex = -1;
  private draft = '';

  private get parsedSubject(): SubjectData | null {
    if (!this.subject) {
      return null;
    }
    try {
      return JSON.parse(this.subject) as SubjectData;
    } catch {
      return null;
    }
  }

  private get userHistory(): string[] {
    return this.messages
      .filter((m): m is { kind: 'user'; text: string } => m.kind === 'user')
      .map(m => m.text)
      .reverse();
  }

  override createRenderRoot(): HTMLElement | DocumentFragment {
    return this;
  }

  protected override firstUpdated(): void {
    this.scrollToBottom();
    this.closest('typo3-backend-modal')?.addEventListener('typo3-modal-shown', this.handleModalShown, { once: true });
  }

  protected override render(): TemplateResult {
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
          <div class="assist-chat__response">
            <typo3-assist-quick-actions-element
              .items=${[{ identifier:'i',text:'Improve readability' }]}
              @typo3-assist-quick-actions-select=${(e: CustomEvent<{ key: string; identifier: string; text: string }>) => this.sendRequest(e.detail.text, { [e.detail.key]: e.detail.identifier })}>
            </typo3-assist-quick-actions-element>
          </div>

          ${this.renderMessages()}
          ${this.isLoading ? this.renderThinking() : nothing}
          ${this.renderInput()}
        </div>
      </div>
    `;
  }

  private readonly handleModalShown = (): void => {
    this.sendRequest('');
  };

  private readonly handleKeyDown = (e: KeyboardEvent): void => {
    if (e.key === 'Enter') {
      this.handleSend();
      return;
    }
    const input = this.querySelector<HTMLInputElement>('.assist-chat__text-input');
    if (!input) { return; }
    const history = this.userHistory;
    if (e.key === 'ArrowUp') {
      if (history.length === 0) { return; }
      e.preventDefault();
      if (this.historyIndex === -1) {
        this.draft = input.value;
      }
      this.historyIndex = Math.min(this.historyIndex + 1, history.length - 1);
      input.value = history[this.historyIndex];
    } else if (e.key === 'ArrowDown') {
      if (this.historyIndex === -1) { return; }
      e.preventDefault();
      this.historyIndex--;
      input.value = this.historyIndex === -1 ? this.draft : history[this.historyIndex];
    }
  };

  private scrollToBottom(): void {
    requestAnimationFrame((): void => {
      const chatBody = this.querySelector<HTMLElement>('.assist-chat');
      if (!chatBody) {
        return;
      }
      chatBody.scrollTop = chatBody.scrollHeight;
    });
  }

  private handleCloseClick(): void {
    this.closest('typo3-backend-modal')?.hideModal();
  }

  private handleSend(): void {
    const input = this.querySelector<HTMLInputElement>('.assist-chat__text-input');
    const text = input?.value.trim() ?? '';
    if (!text) {
      return;
    }
    input!.value = '';
    this.historyIndex = -1;
    this.draft = '';
    this.sendRequest(text);
  }

  private async sendRequest(message: string, params: Record<string, string> = {}): Promise<void> {
    this.isLoading = true;
    if (message !== '') {
      this.messages = [...this.messages, { kind: 'user', text: message }];
    }

    const headers: Record<string, string> = {
      'Content-Type': 'application/json',
    };
    if (this.progressUuid) {
      headers['x-typo3-assist-progress'] = this.progressUuid;
    }

    try {
      const body: Record<string, unknown> = { identifier: this.assistant };
      if (Object.keys(params).length > 0) {
        Object.assign(body, params);
      } else if (message !== '') {
        body.message = message;
      }

      const response = await new AjaxRequest(
        TYPO3.settings.ajaxUrls.assist_gate_client_request
      ).post(body, { headers });
      const data: AssistantServerResponse = await response.resolve();
      if (data.error) {
        this.appendErrorMessage(data.error);
        return;
      }
      this.steps = data.steps ?? [];
      const newEntries: ChatEntry[] = data.feedback.map(item => ({ kind: 'assistant' as const, item }));
      this.messages = [...this.messages, ...newEntries];
      if (data.progress?.uuid) {
        this.progressUuid = data.progress.uuid;
      }
    } catch (e) {
      this.appendErrorMessage(e instanceof Error ? e.message : 'Network error. Please try again.');
    } finally {
      this.isLoading = false;
      this.scrollToBottom();
    }
  }

  private renderSubjectContext(): TemplateResult {
    const s = this.parsedSubject;
    const label = s === null
      ? (this.subject ?? '')
      : s.kind === 'resource' ? s.path : `${s.tableName}:${s.uid} — ${s.propertyName}`;
    return html`
      <p class="assist-chat-header__context text-variant">
        ${label}
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
        <input
          type="text"
          class="form-control assist-chat__text-input"
          placeholder="Tell me what you want to change…"
          autofocus
          @keydown=${this.handleKeyDown}
        >
        <button type="button" class="btn btn-primary assist-chat__input-button" @click=${this.handleSend}>
          <typo3-backend-icon identifier="actions-arrow-up-alt"></typo3-backend-icon>
        </button>
      </div>
    `;
  }

  private renderMessages(): TemplateResult {
    return html`${this.messages.map(entry => {
      if (entry.kind === 'user') {
        return html`<div class="assist-chat__user-input"><p class="assist-chat__user-input-bubble">${entry.text}</p></div>`;
      }
      if (entry.kind === 'error') {
        return html`
          <div class="assist-chat__response assist-chat__response--error">
            <p class="assist-chat__text assist-chat__text--error">${entry.text}</p>
          </div>
        `;
      }
      return this.renderFeedbackItem(entry.item);
    })}`;
  }

  private appendErrorMessage(text: string): void {
    this.messages = [...this.messages, { kind: 'error', text }];
  }

  private renderFeedbackItem(item: AssistFeedbackItem): TemplateResult {
    if (item.type === 'text') {
      return html`
        <div class="assist-chat__response">
          <p class="assist-chat__text">${item.text}</p>
        </div>
      `;
    }
    if (item.type === 'markdown') {
      return html`
        <div class="assist-chat__response assist-chat__response--markdown">
          ${markdown(item.text, 'default')}
        </div>
      `;
    }
    if (item.type === 'confirmation') {
      return html`
        <div class="assist-chat__response">
          <p class="assist-chat__text">${item.text}</p>
          <div class="assist-chat__confirmation-actions">
            <button type="button" class="btn btn-primary" @click=${() => this.sendRequest(item.acceptLabel)}>${item.acceptLabel}</button>
            <button type="button" class="btn btn-default" @click=${() => this.sendRequest(item.declineLabel)}>${item.declineLabel}</button>
          </div>
        </div>
      `;
    }
    if (item.type === 'list') {
      return html`
        <div class="assist-chat__response">
          <typo3-assist-list-element .items=${item.items}></typo3-assist-list-element>
        </div>
      `;
    }
    if (item.type === 'quick-actions') {
      return html`
        <div class="assist-chat__response">
          <typo3-assist-quick-actions-element
            key=${item.key}
            text=${item.text}
            .items=${item.items}
            @typo3-assist-quick-action-select=${(e: CustomEvent<{ key: string; identifier: string; text: string }>) => this.sendRequest(e.detail.text, { [e.detail.key]: e.detail.identifier })}
          ></typo3-assist-quick-actions-element>
        </div>
      `;
    }
    return html`
      <div class="assist-chat__response">
        <typo3-assist-options-element
          key=${item.key}
          text=${item.text}
          .items=${item.options}
          @typo3-assist-option-select=${(e: CustomEvent<{ key: string; identifier: string; text: string }>) => this.sendRequest(e.detail.text, { [e.detail.key]: e.detail.identifier })}
        ></typo3-assist-options-element>
      </div>
    `;
  }


}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-assist-chat-element': ChatElement;
  }
}
