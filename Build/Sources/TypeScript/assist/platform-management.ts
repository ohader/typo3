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

interface ModelInfo {
  name: string;
  capabilities: string[];
  enabled: boolean;
}

/**
 * @todo replace `element.innerHTML` by proper DOM templates
 * @todo use data attributes for platform names, no numeric indices
 */
class PlatformManagement {
  private readonly siteIdentifier: string;

  constructor() {
    const container = document.querySelector('[data-platform-management]') as HTMLElement | null;
    this.siteIdentifier = container?.dataset.siteIdentifier ?? '';

    document.querySelectorAll('[data-action="check-connection"]').forEach((button: Element) => {
      button.addEventListener('click', (e: Event) => {
        e.preventDefault();
        const btn = e.currentTarget as HTMLElement;
        const index = parseInt(btn.dataset.platformIndex ?? '0', 10);
        this.checkConnection(index, btn);
      });
    });

    document.querySelectorAll('[data-action="toggle-models"]').forEach((button: Element) => {
      button.addEventListener('click', (e: Event) => {
        e.preventDefault();
        const btn = e.currentTarget as HTMLElement;
        const index = parseInt(btn.dataset.platformIndex ?? '0', 10);
        this.toggleModels(index);
      });
    });
  }

  private async checkConnection(platformIndex: number, button: HTMLElement): Promise<void> {
    const statusSpan = document.getElementById('connection-status-' + platformIndex);
    if (!statusSpan) {
      return;
    }

    button.classList.add('disabled');
    statusSpan.innerHTML = '<span class="badge badge-info">Checking...</span>';

    try {
      const response = await new AjaxRequest(TYPO3.settings.ajaxUrls.assist_platform_check_connection).post({
        siteIdentifier: this.siteIdentifier,
        platformIndex: platformIndex,
      });
      const data = await response.resolve();

      if (data.success) {
        statusSpan.innerHTML = '<span class="badge badge-success">Connected</span>';
      } else {
        statusSpan.innerHTML = '<span class="badge badge-danger" title="' + this.escapeHtml(data.error || 'Unknown error') + '">Failed</span>';
      }
    } catch {
      statusSpan.innerHTML = '<span class="badge badge-danger">Failed</span>';
    } finally {
      button.classList.remove('disabled');
    }
  }

  private async toggleModels(platformIndex: number): Promise<void> {
    const modelsRow = document.getElementById('models-' + platformIndex);
    if (!modelsRow) {
      return;
    }

    if (modelsRow.style.display !== 'none') {
      modelsRow.style.display = 'none';
      return;
    }

    const cell = modelsRow.querySelector('td') as HTMLTableCellElement;
    cell.innerHTML = '<em>Loading models...</em>';
    modelsRow.style.display = '';

    try {
      const response = await new AjaxRequest(TYPO3.settings.ajaxUrls.assist_platform_get_models).withQueryArguments({
        siteIdentifier: this.siteIdentifier,
        platformIndex: platformIndex,
      }).get();
      const data = await response.resolve();
      const models: ModelInfo[] = data.models ?? [];

      if (models.length === 0) {
        cell.innerHTML = '<em>No models available for this platform.</em>';
        return;
      }

      cell.innerHTML = this.renderModelsTable(models, platformIndex);
      this.bindModelCheckboxes(platformIndex);
    } catch {
      cell.innerHTML = '<em>Failed to load models.</em>';
    }
  }

  private renderModelsTable(models: ModelInfo[], platformIndex: number): string {
    let html = '<table class="table table-sm table-striped mb-0">';
    html += '<thead><tr><th>Model</th><th>Capabilities</th><th>Enabled</th></tr></thead>';
    html += '<tbody>';

    for (const model of models) {
      const badges = model.capabilities.map(
        (cap: string) => '<span class="badge badge-info me-1">' + this.escapeHtml(cap) + '</span>'
      ).join('');

      const btnClass = model.enabled ? 'btn-success' : 'btn-default';
      const btnLabel = model.enabled ? 'Enabled' : 'Disabled';
      html += '<tr>';
      html += '<td><code>' + this.escapeHtml(model.name) + '</code></td>';
      html += '<td>' + badges + '</td>';
      html += '<td><button type="button" class="btn btn-sm ' + btnClass + ' model-toggle" data-platform-index="'
        + platformIndex + '" data-model-name="' + this.escapeHtml(model.name) + '" data-enabled="'
        + (model.enabled ? '1' : '0') + '">' + btnLabel + '</button></td>';
      html += '</tr>';
    }

    html += '</tbody></table>';
    return html;
  }

  private bindModelCheckboxes(platformIndex: number): void {
    const modelsRow = document.getElementById('models-' + platformIndex);
    if (!modelsRow) {
      return;
    }
    modelsRow.querySelectorAll('.model-toggle').forEach((btn: Element) => {
      btn.addEventListener('click', () => {
        const button = btn as HTMLButtonElement;
        const enabled = button.dataset.enabled === '1';
        button.dataset.enabled = enabled ? '0' : '1';
        button.classList.toggle('btn-success', !enabled);
        button.classList.toggle('btn-default', enabled);
        button.textContent = enabled ? 'Disabled' : 'Enabled';
        this.saveModels(platformIndex);
      });
    });
  }

  private async saveModels(platformIndex: number): Promise<void> {
    const modelsRow = document.getElementById('models-' + platformIndex);
    if (!modelsRow) {
      return;
    }

    const enabledModels: string[] = [];
    modelsRow.querySelectorAll('.model-toggle').forEach((btn: Element) => {
      const button = btn as HTMLButtonElement;
      if (button.dataset.enabled === '1') {
        const name = button.dataset.modelName ?? '';
        if (name) {
          enabledModels.push(name);
        }
      }
    });

    try {
      const response = await new AjaxRequest(TYPO3.settings.ajaxUrls.assist_platform_update_models).post({
        siteIdentifier: this.siteIdentifier,
        platformIndex: platformIndex,
        models: enabledModels,
      });
      const data = await response.resolve();

      if (data.success) {
        Notification.success('Models updated', 'Model preferences have been saved.', 3);
      } else {
        Notification.error('Error', data.error || 'Failed to save model preferences.');
      }
    } catch {
      Notification.error('Error', 'Failed to save model preferences.');
    }
  }

  private escapeHtml(text: string): string {
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(text));
    return div.innerHTML;
  }
}

export default new PlatformManagement();
