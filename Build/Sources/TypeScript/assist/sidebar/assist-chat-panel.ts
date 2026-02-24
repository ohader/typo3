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

import { html, LitElement, nothing, type TemplateResult } from 'lit';
import { customElement, property, state } from 'lit/decorators.js';
import { classMap } from 'lit/directives/class-map.js';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import type { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import '@typo3/backend/element/spinner-element';
import '@typo3/backend/element/icon-element';
import type { AssistantServerResponseProgress, AssistantServerResponseResult } from '../responses';

/**
 * A single message in the conversation.
 */
export interface ChatMessage {
  role: 'user' | 'assistant';
  content: string;
  timestamp?: string;
}

/**
 * A reference to a TCA field on a specific record.
 */
export interface TcaResource {
  type: 'TcaResource';
  tableName: string;
  identifier: number | string;
  propertyName: string;
}

/**
 * Context forwarded from the field wizard to the chat panel.
 */
export interface AssistContext {
  resource: TcaResource;
  currentValue?: string;
}

/**
 * Module: @typo3/assist/sidebar/assist-chat-panel
 *
 * Primary conversational interface rendered in the TYPO3 sidebar. Owns the
 * full AJAX request/response cycle — fetches a greeting on init and sends
 * each user message to `assist_gate_client_request`.
 *
 * CSS custom properties:
 *   --assist-panel-header-bg        Header background (default: #1a1a1a)
 *   --assist-panel-accent           Accent colour (default: #f47f00)
 *   --assist-panel-user-bubble-bg   User message bubble background
 *   --assist-panel-width            Panel width in sidebar mode (default: 360px)
 *
 * Events:
 *   assist:clear       – Clear conversation
 *   assist:export      – Export conversation
 *   assist:close       – Close panel
 *   assist:new-session – Start fresh session
 *   assist:apply       – detail: { content: string, resource: TcaResource }
 *
 * @example
 * <typo3-assist-chat-panel mode="sidebar"></typo3-assist-chat-panel>
 */
@customElement('typo3-assist-chat-panel')
export class AssistChatPanel extends LitElement {
  @property({ type: String }) mode: 'sidebar' | 'expanded' = 'sidebar';
  @property({ type: String, attribute: 'session-id' }) sessionId: string = '';
  @property({ type: Object }) usage: { used: number; total: number } = { used: 0, total: 100 };
  @property({ type: String }) assistant: string = '';
  @property({ type: Object }) context: AssistContext | null = null;

  @state() private selectedModel: string = 'gpt-4o-mini';
  @state() private inputValue: string = '';
  @state() private showMoreMenu: boolean = false;
  @state() private messages: ChatMessage[] = [];
  @state() private isStreaming: boolean = false;
  @state() private progressUuid: string = '';

  private readonly availableModels: string[] = [
    'gpt-4o-mini',
    'gpt-4o',
    'claude-sonnet-4-6',
    'claude-haiku-4-5',
  ];

  private get usagePercent(): number {
    return this.usage.total > 0
      ? Math.min(100, Math.round((this.usage.used / this.usage.total) * 100))
      : 0;
  }

  override createRenderRoot(): HTMLElement {
    return this;
  }

  protected override updated(changedProperties: Map<string, unknown>): void {
    if (changedProperties.has('assistant') && this.assistant && !changedProperties.get('assistant')) {
      void this.initiate();
    }
  }

  public override connectedCallback(): void {
    console.log('chat');
    super.connectedCallback();
  }

  public override disconnectedCallback(): void {
    super.disconnectedCallback();
  }

  protected override render(): TemplateResult {
    return html`
      <div class="panel-header">
        <div class="panel-title">
          <typo3-backend-icon identifier="module-assist" size="small"></typo3-backend-icon>
          TYPO3 AI Assist
        </div>
        <div class="panel-header-actions">
          <div class="more-menu-wrapper">
            <button
              type="button"
              class="icon-btn icon-btn-label"
              @click=${this.toggleMoreMenu}
              aria-expanded=${this.showMoreMenu ? 'true' : 'false'}
              aria-haspopup="true"
            >
              More
              <typo3-backend-icon identifier="actions-chevron-down" size="small"></typo3-backend-icon>
            </button>

            ${this.showMoreMenu ? html`
              <div class="more-menu" role="menu">
                <button type="button" class="more-menu-item" role="menuitem" @click=${this.handleExport}>
                  <typo3-backend-icon identifier="actions-document-export-t3d" size="small"></typo3-backend-icon>
                  Export conversation
                </button>
                <button type="button" class="more-menu-item" role="menuitem" @click=${this.handleClear}>
                  <typo3-backend-icon identifier="actions-delete" size="small"></typo3-backend-icon>
                  Clear chat
                </button>
                <button type="button" class="more-menu-item" role="menuitem" @click=${this.handleNewSession}>
                  <typo3-backend-icon identifier="actions-add" size="small"></typo3-backend-icon>
                  Start new chat
                </button>
                <div class="more-menu-separator" role="separator"></div>
                <div class="more-menu-section-label">Settings</div>
                <div class="more-menu-item">
                  <typo3-backend-icon identifier="actions-system-options-view" size="small"></typo3-backend-icon>
                  Model / Temperature / Tokens
                </div>
              </div>
            ` : nothing}
          </div>

          <button type="button" class="icon-btn" @click=${this.handleClose} aria-label="Close panel">
            <typo3-backend-icon identifier="actions-close" size="small"></typo3-backend-icon>
          </button>
        </div>
      </div>

      <div class="panel-meta">
        <div class="model-selector">
          <typo3-backend-icon identifier="actions-system-options-view" size="small"></typo3-backend-icon>
          <select
            class="model-select"
            .value=${this.selectedModel}
            @change=${(e: Event) => { this.selectedModel = (e.target as HTMLSelectElement).value; }}
            aria-label="Select AI model"
          >
            ${this.availableModels.map(m => html`
              <option value=${m} ?selected=${this.selectedModel === m}>${m}</option>
            `)}
          </select>
        </div>
        <div class="usage-indicator">
          ${this.context ? html`<span class="field-badge">${this.context.resource.propertyName}</span>` : nothing}
          Usage: ${this.usagePercent}%
          <div class="usage-bar" aria-hidden="true">
            <div class="usage-bar-fill" style="width: ${this.usagePercent}%"></div>
          </div>
        </div>
      </div>

      <div class="messages-area" role="log" aria-live="polite" aria-label="Conversation">
        ${this.messages.length === 0 ? html`
          <div class="messages-empty">
            <span class="messages-empty-icon">
              <typo3-backend-icon identifier="module-assist" size="large"></typo3-backend-icon>
            </span>
            <span class="messages-empty-text">Ask Assist anything about your TYPO3 project.</span>
          </div>
        ` : html`
          ${this.messages.map(msg => this.renderMessage(msg))}
          ${this.isStreaming ? html`
            <div class="streaming-indicator" aria-live="polite">
              <typo3-backend-spinner size="small"></typo3-backend-spinner>
              Assist is thinking…
            </div>
          ` : nothing}
        `}
      </div>

      <div class="panel-input-area">
        <div class="input-row">
          <textarea
            class="input-field"
            rows="1"
            placeholder="Ask Assist…"
            .value=${this.inputValue}
            @input=${this.handleInputInput}
            @keydown=${this.handleInputKeydown}
            ?disabled=${this.isStreaming}
            aria-label="Message input"
          ></textarea>
          <button
            type="button"
            class="send-btn"
            @click=${this.handleSend}
            ?disabled=${!this.inputValue.trim() || this.isStreaming}
            aria-label="Send message"
          >
            <typo3-backend-icon identifier="actions-arrow-right" size="small"></typo3-backend-icon>
          </button>
        </div>
        <p class="input-disclaimer">AI output may be wrong. Verify before using.</p>
      </div>
    `;
  }

  private handleSend(): void {
    const message = this.inputValue.trim();
    if (!message || this.isStreaming) {
      return;
    }
    this.messages = [...this.messages, { role: 'user', content: message }];
    this.inputValue = '';
    void this.doRequest(message);
  }

  private handleInputKeydown(e: KeyboardEvent): void {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      this.handleSend();
    }
  }

  private handleInputInput(e: Event): void {
    this.inputValue = (e.target as HTMLTextAreaElement).value;
  }

  private handleClear(): void {
    this.showMoreMenu = false;
    this.messages = [];
    this.dispatchEvent(new CustomEvent('assist:clear', { bubbles: true, composed: true }));
  }

  private handleExport(): void {
    this.showMoreMenu = false;
    this.dispatchEvent(new CustomEvent('assist:export', { bubbles: true, composed: true }));
  }

  private handleClose(): void {
    this.dispatchEvent(new CustomEvent('assist:close', { bubbles: true, composed: true }));
  }

  private handleNewSession(): void {
    this.showMoreMenu = false;
    this.messages = [];
    this.progressUuid = '';
    void this.initiate();
    this.dispatchEvent(new CustomEvent('assist:new-session', { bubbles: true, composed: true }));
  }

  private toggleMoreMenu(): void {
    this.showMoreMenu = !this.showMoreMenu;
  }

  private async initiate(): Promise<void> {
    await this.doRequest();
  }

  private async doRequest(message?: string): Promise<void> {
    this.isStreaming = true;
    const body: Record<string, unknown> = {
      identifier: this.assistant,
      context: this.context,
      model: this.selectedModel,
    };
    if (message) { body.message = message; }
    if (this.progressUuid) { body.progressUuid = this.progressUuid; }
    try {
      const data = await new AjaxRequest(TYPO3.settings.ajaxUrls.assist_gate_client_request)
        .post(body, { headers: { 'Content-Type': 'application/json' } })
        .then(async (r: AjaxResponse) => r.resolve()) as AssistantServerResponseResult | AssistantServerResponseProgress;
      this.processResponse(data);
    } finally {
      this.isStreaming = false;
    }
  }

  private processResponse(data: AssistantServerResponseResult | AssistantServerResponseProgress): void {
    if (data.type === 'progress:start') {
      this.progressUuid = data.progress.uuid;
      return;
    }
    if (data.type === 'result') {
      const newMessages: ChatMessage[] = data.results.map(item => ({
        role: 'assistant' as const,
        content: item.content,
        timestamp: data.timestamp,
      }));
      this.messages = [...this.messages, ...newMessages];
    }
  }

  private applyToField(content: string): void {
    if (!this.context) { return; }
    const { tableName, identifier, propertyName } = this.context.resource;
    const field = document.querySelector<HTMLInputElement | HTMLTextAreaElement>(
      `[name="data[${tableName}][${identifier}][${propertyName}]"]`,
    );
    if (field) {
      field.value = content;
      field.dispatchEvent(new Event('input', { bubbles: true }));
      field.dispatchEvent(new Event('change', { bubbles: true }));
    }
    this.dispatchEvent(new CustomEvent('assist:apply', {
      bubbles: true,
      composed: true,
      detail: { content, resource: this.context.resource },
    }));
  }

  private renderMessage(msg: ChatMessage): TemplateResult {
    return html`
      <div class=${classMap({ message: true, 'message-user': msg.role === 'user', 'message-assistant': msg.role === 'assistant' })}>
        <div class="message-bubble">${msg.content}</div>
        ${msg.timestamp ? html`<span class="message-timestamp">${msg.timestamp}</span>` : nothing}
        ${this.context && msg.role === 'assistant' ? html`<button type="button" class="apply-btn" @click=${() => this.applyToField(msg.content)}>Apply to field</button>` : nothing}
      </div>
    `;
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-assist-chat-panel': AssistChatPanel;
  }
}
