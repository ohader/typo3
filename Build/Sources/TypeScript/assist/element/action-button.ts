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

import { customElement } from 'lit/decorators.js';
import { html } from 'lit';
import { PseudoButtonLitElement } from '@typo3/backend/element/pseudo-button';
import Modal, { Sizes, Positions } from '@typo3/backend/modal';
import { SeverityEnum } from '@typo3/backend/enum/severity';

type AssistTemplate = 'meta' | 'media' | 'alt';

const templateTitles: Record<AssistTemplate, string> = {
  meta: 'Generate Meta Description',
  media: 'Generate Media',
  alt: 'Generate Image Alternative Text',
};

const normalizeTemplate = (template: string | null): AssistTemplate => {
  if (template === 'media' || template === 'alt') {
    return template;
  }
  return 'meta';
};

const openAssistModal = async (template: AssistTemplate): Promise<void> => {
  await import('@typo3/assist/element/chat-element');
  Modal.advanced({
    title: templateTitles[template],
    additionalCssClasses: ['assist-chat-modal'],
    severity: SeverityEnum.notice,
    size: Sizes.large,
    position: Positions.bottom,
    content: html`<typo3-assist-chat-element template="${template}"></typo3-assist-chat-element>`,
    staticBackdrop: true,
    hideHeader: true,
  });
};

/**
 * Module: @typo3/assist/element/action-button
 *
 * @example
 * <typo3-backend-assist-trigger class="btn btn-default btn-sm"></typo3-backend-assist-trigger>
 */
@customElement('typo3-assist-action-button')
export class ActionButton extends PseudoButtonLitElement {
  protected override async buttonActivated(): Promise<void> {
    await openAssistModal(normalizeTemplate(this.getAttribute('template')));
  }
}

document.addEventListener('click', (event: Event): void => {
  const target = event.target as Element | null;
  if (!target) {
    return;
  }
  const triggerItem = target.closest<HTMLElement>('.t3js-assist-trigger-item[data-assist-template]');
  if (!triggerItem) {
    return;
  }
  event.preventDefault();
  void openAssistModal(normalizeTemplate(triggerItem.dataset.assistTemplate ?? null));
});

declare global {
  interface HTMLElementTagNameMap {
    'typo3-assist-action-button': ActionButton;
  }
}
