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

interface ToolExecutionResponse {
  toolCallId: string;
  result: string;
}

export async function executeTool(
  assistantIdentifier: string,
  toolName: string,
  toolCallId: string,
  args: Record<string, unknown>,
): Promise<string> {
  const response = await new AjaxRequest(
    TYPO3.settings.ajaxUrls.assist_browser_execute_tool
  ).post({
    assistant: assistantIdentifier,
    toolName,
    toolCallId,
    arguments: args,
  }, {
    headers: { 'Content-Type': 'application/json' },
  });
  const data: ToolExecutionResponse = await response.resolve();
  return data.result;
}
