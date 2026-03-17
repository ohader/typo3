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

import { html } from 'lit';
import Modal, { Sizes, Positions } from '@typo3/backend/modal';
import { SeverityEnum } from '@typo3/backend/enum/severity';
import type { AssistChatProperties } from '@typo3/assist/element/chat-element';
import type { LabelProvider } from '@typo3/backend/localization/label-provider';

/*
const templateTitles: Record<AssistTemplate, string> = {
  meta: 'Generate Meta Description',
  media: 'Generate Media',
  alt: 'Generate Image Alternative Text',
};
*/

export const openAssistModal = async (properties: AssistChatProperties): Promise<void> => {
  if (properties.additionalModule) {
    void import(properties.additionalModule + '.js');
  }
  await import('@typo3/assist/element/chat-element');
  const { default: labels }: { default: LabelProvider<any> } = await import('~labels/' + properties.labelDomain);
  Modal.advanced({
    title: labels.get('chat.title'),
    additionalCssClasses: ['assist-chat-modal'],
    severity: SeverityEnum.notice,
    size: Sizes.large,
    position: Positions.bottom,
    content: html`<typo3-assist-chat-element
      .subject=${properties.subject}
      .assistant=${properties.assistant}
      .labels=${labels}
      .input=${properties.input ?? 'optional'}
    ></typo3-assist-chat-element>`,
    staticBackdrop: true,
    hideHeader: true,
  });
};

/**
 * Module: @typo3/assist/assist-trigger
 */
document.addEventListener('click', (event: Event): void => {
  const target = event.target as Element | null;
  if (!target) {
    return;
  }
  const triggerItem = target.closest<HTMLElement>('.t3js-assist-trigger-item[data-assistant-identifier]');
  if (!triggerItem) {
    return;
  }
  event.preventDefault();
  const properties = {
    additionalModule: triggerItem.dataset.assistantModule,
    subject: triggerItem.dataset.assistantSubject,
    assistant: triggerItem.dataset.assistantIdentifier,
    labelDomain: triggerItem.dataset.assistantLabelDomain,
    input: triggerItem.dataset.assistantInput as 'optional' | 'visible' | 'hidden' | undefined,
  };
  void openAssistModal(properties as AssistChatProperties);
});
