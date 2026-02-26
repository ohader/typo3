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

import { html, LitElement, type TemplateResult } from 'lit';
import { customElement, property } from 'lit/decorators.js';
import Modal, { Sizes } from '@typo3/backend/modal';
import { SeverityEnum } from '@typo3/backend/enum/severity';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import type { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import '@typo3/backend/element/icon-element';
import '@typo3/assist/sidebar/assist-chat-panel';
import type { AssistContext, TcaResource } from '@typo3/assist/sidebar/assist-chat-panel';

/**
 * Module: @typo3/assist/element/assist-action-element
 *
 * Reusable entry-point for contextual AI assistance triggered from within
 * form fields. Renders an "Assist" button that can be placed below a TCA
 * field via the standard fieldWizard mechanism.
 *
 * @example
 * <typo3-assist-action
 *   trigger-resource="tt_content:42"
 *   trigger-component="resource-edit"
 *   label="Assist"
 * ></typo3-assist-action>
 *
 * @deprecated use action-button.ts instead
 */
@customElement('typo3-assist-action')
export class ActionElement extends LitElement {
  @property({
    attribute: 'trigger-resource',
    converter: {
      fromAttribute: (value: string | null): TcaResource | null => {
        if (!value) {
          return null;
        }
        try {
          return JSON.parse(value) as TcaResource;
        } catch {
          return null;
        }
      },
    },
  }) triggerResource: TcaResource | null = null;
  @property({ type: String, attribute: 'trigger-component' }) triggerComponent: string = '';
  @property({ type: String }) label: string = 'Assist';
  @property({
    converter: {
      fromAttribute: (value: string | null): string[] =>
        value ? value.trim().split(/\s+/).filter(Boolean) : [],
    },
  }) assistants: string[] = [];

  override createRenderRoot(): HTMLElement {
    return this;
  }

  protected override render(): TemplateResult {
    return html`
      <button type="button" class="btn btn-info btn-sm" @click=${this.handleClick}>
        <typo3-backend-icon identifier="module-assist" size="small"></typo3-backend-icon>
        ${this.label}
      </button>
    `;
  }

  private handleClick(): void {
    const list = this.assistants;
    if (list.length === 1) {
      this.openAssistantModal(list[0], list[0]);
      return;
    }
    void this.openForAssistants(list);
  }

  private async openForAssistants(filter: string[]): Promise<void> {
    // resolves the available & allowed assistants for the current scope
    const data: { identifier: string; label: string }[] = await new AjaxRequest(
      TYPO3.settings.ajaxUrls.assist_get_inline_assistants,
    )
      .post(
        { triggerResource: this.triggerResource },
        { headers: { 'Content-Type': 'application/json' } },
      )
      .then(async (r: AjaxResponse) => r.resolve());

    const assistants = filter.length > 0
      ? data.filter((a) => filter.includes(a.identifier))
      : data;

    if (assistants.length === 0) {
      return;
    }
    if (assistants.length === 1) {
      this.openAssistantModal(assistants[0].identifier, assistants[0].label);
      return;
    }
    this.openSelectionModal(assistants);
  }

  private openSelectionModal(assistants: { identifier: string; label: string }[]): void {
    Modal.advanced({
      title: 'Select an Assistant',
      severity: SeverityEnum.notice,
      size: Sizes.small,
      content: html`
        <div class="list-group">
          ${assistants.map((a) => html`
            <button
              type="button"
              class="list-group-item list-group-item-action"
              @click=${() => this.handleSelectionClick(a)}
            >
              <typo3-backend-icon identifier="module-assist" size="small"></typo3-backend-icon>
              ${a.label || a.identifier}
            </button>
          `)}
        </div>
      `,
      buttons: [{ text: 'Cancel', btnClass: 'btn-default', trigger: (_, m) => m.hideModal() }],
    });
  }

  private openAssistantModal(identifier: string, label: string): void {
    const context: AssistContext | null = this.triggerResource
      ? { resource: this.triggerResource, currentValue: this.getCurrentFieldValue() }
      : null;

    Modal.advanced({
      title: label || identifier,
      severity: SeverityEnum.notice,
      size: Sizes.large,
      content: html`
        <typo3-assist-chat-panel
          .assistant=${identifier}
          .context=${context}
          style="height:60vh;display:block"
        ></typo3-assist-chat-panel>
      `,
      buttons: [{ text: 'Close', btnClass: 'btn-default', trigger: (_, m) => m.hideModal() }],
    });
  }

  private getCurrentFieldValue(): string {
    if (!this.triggerResource) {
      return '';
    }
    const { tableName, identifier, propertyName } = this.triggerResource;
    const field = document.querySelector<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>(
      `[name="data[${tableName}][${identifier}][${propertyName}]"]`,
    );
    return field?.value ?? '';
  }

  private handleSelectionClick(a: { identifier: string; label: string }): void {
    Modal.dismiss();
    this.openAssistantModal(a.identifier, a.label);
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-assist-action': ActionElement;
  }
}
