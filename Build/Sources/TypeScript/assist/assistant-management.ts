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

import AjaxRequest from '@typo3/core/ajax/ajax-request';
import Notification from '@typo3/backend/notification';

interface MatchingModel {
  identifier: string;
  model: string;
  platform: string;
}

class AssistantManagement {
  private readonly siteIdentifier: string;
  private readonly assistantModels: Record<string, string>;

  constructor() {
    const container = document.querySelector('[data-assistant-management]') as HTMLElement | null;
    this.siteIdentifier = container?.dataset.siteIdentifier ?? '';
    this.assistantModels = JSON.parse(container?.dataset.assistantModels ?? '{}');

    if (!this.siteIdentifier) {
      return;
    }

    document.querySelectorAll('select[data-action="select-model"]').forEach((select: Element) => {
      const selectElement = select as HTMLSelectElement;
      const assistantIdentifier = selectElement.dataset.assistantIdentifier ?? '';
      if (assistantIdentifier) {
        this.loadMatchingModels(selectElement, assistantIdentifier);
        selectElement.addEventListener('change', () => {
          this.updateAssistantModel(assistantIdentifier, selectElement.value);
        });
      }
    });
  }

  private async loadMatchingModels(select: HTMLSelectElement, assistantIdentifier: string): Promise<void> {
    try {
      const response = await new AjaxRequest(TYPO3.settings.ajaxUrls.assist_assistant_get_matching_models).withQueryArguments({
        siteIdentifier: this.siteIdentifier,
        assistantIdentifier: assistantIdentifier,
      }).get();
      const data = await response.resolve();
      const models: MatchingModel[] = data.models ?? [];

      select.innerHTML = '';

      const emptyOption = document.createElement('option');
      emptyOption.value = '';
      emptyOption.textContent = '— None —';
      select.appendChild(emptyOption);

      for (const model of models) {
        const option = document.createElement('option');
        option.value = model.identifier;
        option.textContent = model.model + ' (' + model.platform + ')';
        select.appendChild(option);
      }

      const currentModel = this.assistantModels[assistantIdentifier] ?? '';
      if (currentModel) {
        select.value = currentModel;
      }
    } catch {
      select.innerHTML = '<option value="">Failed to load models</option>';
    }
  }

  private async updateAssistantModel(assistantIdentifier: string, model: string): Promise<void> {
    try {
      const response = await new AjaxRequest(TYPO3.settings.ajaxUrls.assist_assistant_update_model).post({
        siteIdentifier: this.siteIdentifier,
        assistantIdentifier: assistantIdentifier,
        model: model,
      });
      const data = await response.resolve();

      if (data.success) {
        this.assistantModels[assistantIdentifier] = model;
        Notification.success('Model updated', 'Assistant model preference has been saved.', 3);
      } else {
        Notification.error('Error', data.error || 'Failed to save model preference.');
      }
    } catch {
      Notification.error('Error', 'Failed to save model preference.');
    }
  }
}

export default new AssistantManagement();
