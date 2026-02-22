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
 * A single message in the conversation.
 */
export interface ChatMessage {
  role: 'user' | 'assistant';
  content: string;
  timestamp?: string;
}

/**
 * Module: @typo3/assist/sidebar/assist-chat-panel
 *
 * Primary conversational interface rendered in the TYPO3 sidebar.  Pure UI
 * component — dispatches events for the host to handle SSE streaming, AJAX,
 * session persistence, etc.
 *
 * CSS custom properties:
 *   --assist-panel-header-bg        Header background (default: #1a1a1a)
 *   --assist-panel-accent           Accent colour (default: #f47f00)
 *   --assist-panel-user-bubble-bg   User message bubble background
 *   --assist-panel-width            Panel width in sidebar mode (default: 360px)
 *
 * Events:
 *   assist:send        – detail: { message: string, model: string }
 *   assist:clear       – Clear conversation
 *   assist:export      – Export conversation
 *   assist:close       – Close panel
 *   assist:new-session – Start fresh session
 *
 * @example
 * <typo3-assist-chat-panel mode="sidebar"></typo3-assist-chat-panel>
 */
@customElement('typo3-assist-chat-panel')
export class AssistChatPanel extends LitElement {
  static override styles = css`
    :host {
      --assist-panel-header-bg: #1a1a1a;
      --assist-panel-accent: #f47f00;
      --assist-panel-user-bubble-bg: color-mix(in srgb, var(--assist-panel-accent) 12%, var(--typo3-component-bg, #fff));
      --assist-panel-width: 360px;
      display: flex;
      flex-direction: column;
      width: var(--assist-panel-width);
      height: 100%;
      background: var(--typo3-component-bg, #fff);
      color: var(--typo3-component-color, #333);
      font-family: inherit;
      font-size: 0.875rem;
      overflow: hidden;
    }

    :host([mode="expanded"]) {
      --assist-panel-width: 100%;
    }

    :host([hidden]) {
      display: none;
    }

    /* ── Header ── */
    .panel-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0.625rem 0.875rem;
      background: var(--assist-panel-header-bg);
      color: #fff;
      flex-shrink: 0;
      gap: 0.5rem;
    }

    .panel-title {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      font-weight: 600;
      font-size: 0.9375rem;
      flex: 1;
      min-width: 0;
    }

    .panel-header-actions {
      display: flex;
      align-items: center;
      gap: 0.25rem;
    }

    .icon-btn {
      display: flex;
      align-items: center;
      justify-content: center;
      background: none;
      border: none;
      color: #fff;
      cursor: pointer;
      padding: 0.25rem;
      border-radius: 2px;
      opacity: 0.8;
      position: relative;
    }

    .icon-btn:hover {
      opacity: 1;
      background: rgba(255, 255, 255, 0.1);
    }

    .icon-btn-label {
      font-size: 0.8125rem;
      display: flex;
      align-items: center;
      gap: 0.25rem;
      padding: 0.25rem 0.5rem;
    }

    /* ── Meta bar (model + usage) ── */
    .panel-meta {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0.375rem 0.875rem;
      background: var(--typo3-component-bg-secondary, #f8f9fa);
      border-bottom: 1px solid var(--typo3-component-border-color, #dee2e6);
      font-size: 0.75rem;
      color: var(--typo3-muted-color, #6c757d);
      flex-shrink: 0;
    }

    .model-selector {
      display: flex;
      align-items: center;
      gap: 0.375rem;
    }

    .model-select {
      border: none;
      background: transparent;
      font-size: 0.75rem;
      color: var(--typo3-component-color, #333);
      font-family: inherit;
      cursor: pointer;
      padding: 0;
    }

    .usage-indicator {
      display: flex;
      align-items: center;
      gap: 0.375rem;
    }

    .usage-bar {
      width: 48px;
      height: 4px;
      background: var(--typo3-component-border-color, #dee2e6);
      border-radius: 2px;
      overflow: hidden;
    }

    .usage-bar-fill {
      height: 100%;
      background: var(--assist-panel-accent);
      border-radius: 2px;
      transition: width 0.3s ease-in-out;
    }

    /* ── Messages ── */
    .messages-area {
      flex: 1;
      overflow-y: auto;
      padding: 1rem 0.875rem;
      display: flex;
      flex-direction: column;
      gap: 0.75rem;
      scroll-behavior: smooth;
    }

    .messages-empty {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      flex: 1;
      gap: 0.75rem;
      color: var(--typo3-muted-color, #6c757d);
      text-align: center;
      padding: 2rem 1rem;
    }

    .messages-empty-icon {
      opacity: 0.4;
    }

    .messages-empty-text {
      font-size: 0.875rem;
    }

    .message {
      display: flex;
      flex-direction: column;
      gap: 0.25rem;
      max-width: 90%;
    }

    .message-user {
      align-self: flex-end;
      align-items: flex-end;
    }

    .message-assistant {
      align-self: flex-start;
      align-items: flex-start;
    }

    .message-bubble {
      padding: 0.5rem 0.75rem;
      border-radius: 12px;
      line-height: 1.5;
      word-break: break-word;
      white-space: pre-wrap;
    }

    .message-user .message-bubble {
      background: var(--assist-panel-user-bubble-bg);
      border-bottom-right-radius: 3px;
    }

    .message-assistant .message-bubble {
      background: var(--typo3-component-bg-secondary, #f8f9fa);
      border: 1px solid var(--typo3-component-border-color, #dee2e6);
      border-bottom-left-radius: 3px;
    }

    .message-timestamp {
      font-size: 0.6875rem;
      color: var(--typo3-muted-color, #adb5bd);
      padding: 0 0.25rem;
    }

    .streaming-indicator {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.5rem 0.75rem;
      background: var(--typo3-component-bg-secondary, #f8f9fa);
      border: 1px solid var(--typo3-component-border-color, #dee2e6);
      border-radius: 12px;
      border-bottom-left-radius: 3px;
      align-self: flex-start;
      color: var(--typo3-muted-color, #6c757d);
      font-size: 0.8125rem;
    }

    /* ── Input area ── */
    .panel-input-area {
      flex-shrink: 0;
      border-top: 1px solid var(--typo3-component-border-color, #dee2e6);
      padding: 0.75rem 0.875rem 0.5rem;
      background: var(--typo3-component-bg, #fff);
    }

    .input-row {
      display: flex;
      align-items: flex-end;
      gap: 0.5rem;
    }

    .input-field {
      flex: 1;
      border: 1px solid var(--typo3-component-border-color, #dee2e6);
      border-radius: 6px;
      padding: 0.5rem 0.75rem;
      font-size: 0.875rem;
      font-family: inherit;
      line-height: 1.5;
      resize: none;
      background: var(--typo3-component-bg, #fff);
      color: var(--typo3-component-color, #333);
      max-height: 120px;
      overflow-y: auto;
      transition: border-color 0.15s ease-in-out;
    }

    .input-field:focus {
      outline: none;
      border-color: var(--assist-panel-accent);
      box-shadow: 0 0 0 2px color-mix(in srgb, var(--assist-panel-accent) 20%, transparent);
    }

    .input-field::placeholder {
      color: var(--typo3-muted-color, #adb5bd);
    }

    .send-btn {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 2.25rem;
      height: 2.25rem;
      border-radius: 6px;
      border: none;
      background: var(--assist-panel-accent);
      color: #fff;
      cursor: pointer;
      flex-shrink: 0;
      transition: opacity 0.15s ease-in-out;
    }

    .send-btn:hover:not(:disabled) {
      opacity: 0.9;
    }

    .send-btn:disabled {
      opacity: 0.5;
      cursor: not-allowed;
    }

    .input-disclaimer {
      font-size: 0.6875rem;
      color: var(--typo3-muted-color, #adb5bd);
      margin-top: 0.375rem;
      text-align: center;
    }

    /* ── More menu ── */
    .more-menu-wrapper {
      position: relative;
    }

    .more-menu {
      position: absolute;
      top: calc(100% + 4px);
      right: 0;
      min-width: 200px;
      background: var(--typo3-component-bg, #fff);
      border: 1px solid var(--typo3-component-border-color, #dee2e6);
      border-radius: 4px;
      box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
      z-index: 100;
      padding: 0.25rem 0;
    }

    .more-menu-item {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.5rem 0.875rem;
      cursor: pointer;
      font-size: 0.8125rem;
      color: var(--typo3-component-color, #333);
      background: none;
      border: none;
      width: 100%;
      text-align: left;
      font-family: inherit;
    }

    .more-menu-item:hover {
      background: var(--typo3-component-bg-secondary, #f8f9fa);
    }

    .more-menu-separator {
      height: 1px;
      background: var(--typo3-component-border-color, #dee2e6);
      margin: 0.25rem 0;
    }

    .more-menu-section-label {
      font-size: 0.6875rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      color: var(--typo3-muted-color, #6c757d);
      padding: 0.375rem 0.875rem 0.125rem;
    }
  `;

  @property({ type: String }) mode: 'sidebar' | 'expanded' = 'sidebar';
  @property({ type: String, attribute: 'session-id' }) sessionId: string = '';
  @property({ type: Array }) messages: ChatMessage[] = [];
  @property({ type: Boolean }) isStreaming: boolean = false;
  @property({ type: Object }) usage: { used: number; total: number } = { used: 0, total: 100 };

  @state() private selectedModel: string = 'gpt-4o-mini';
  @state() private inputValue: string = '';
  @state() private showMoreMenu: boolean = false;

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
    this.inputValue = '';
    this.dispatchEvent(new CustomEvent('assist:send', {
      bubbles: true,
      composed: true,
      detail: { message, model: this.selectedModel },
    }));
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
    this.dispatchEvent(new CustomEvent('assist:new-session', { bubbles: true, composed: true }));
  }

  private toggleMoreMenu(): void {
    this.showMoreMenu = !this.showMoreMenu;
  }

  private renderMessage(msg: ChatMessage): TemplateResult {
    return html`
      <div class=${classMap({ message: true, 'message-user': msg.role === 'user', 'message-assistant': msg.role === 'assistant' })}>
        <div class="message-bubble">${msg.content}</div>
        ${msg.timestamp ? html`<span class="message-timestamp">${msg.timestamp}</span>` : nothing}
      </div>
    `;
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-assist-chat-panel': AssistChatPanel;
  }
}
