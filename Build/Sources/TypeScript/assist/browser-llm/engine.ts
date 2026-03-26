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

import { CreateMLCEngine, type MLCEngine } from '@mlc-ai/web-llm';
import type {
  ChatCompletionMessageParam,
  ChatCompletionTool,
  ChatCompletionMessageToolCall,
} from '@mlc-ai/web-llm';

export type ToolCallHandler = (toolName: string, toolCallId: string, args: Record<string, unknown>) => Promise<string>;

export class BrowserLlmEngine {
  private engine: MLCEngine | null = null;
  private currentModelId: string | null = null;

  async ensureLoaded(modelId: string, onProgress: (text: string) => void): Promise<void> {
    if (this.engine !== null && this.currentModelId === modelId) {
      return;
    }
    this.engine = await CreateMLCEngine(modelId, {
      initProgressCallback: (report) => {
        onProgress(report.text);
      },
    });
    this.currentModelId = modelId;
  }

  async chat(
    messages: ChatCompletionMessageParam[],
    tools: ChatCompletionTool[],
    onToolCall: ToolCallHandler,
  ): Promise<string> {
    if (this.engine === null) {
      throw new Error('BrowserLlmEngine: engine not loaded');
    }

    const conversation: ChatCompletionMessageParam[] = [...messages];
    const requestTools = tools.length > 0 ? tools : undefined;

    for (;;) {
      const response = await this.engine.chat.completions.create({
        messages: conversation,
        tools: requestTools,
        stream: false,
      });

      const choice = response.choices[0];
      const message = choice.message;
      conversation.push({ role: 'assistant', content: message.content ?? '', tool_calls: message.tool_calls });

      if (choice.finish_reason !== 'tool_calls' || !message.tool_calls || message.tool_calls.length === 0) {
        return message.content ?? '';
      }

      for (const toolCall of message.tool_calls as ChatCompletionMessageToolCall[]) {
        let args: Record<string, unknown> = {};
        try {
          args = JSON.parse(toolCall.function.arguments) as Record<string, unknown>;
        } catch {
          // keep empty args
        }
        const result = await onToolCall(toolCall.function.name, toolCall.id, args);
        conversation.push({
          role: 'tool',
          tool_call_id: toolCall.id,
          content: result,
        });
      }
    }
  }
}

export const browserLlmEngine = new BrowserLlmEngine();
