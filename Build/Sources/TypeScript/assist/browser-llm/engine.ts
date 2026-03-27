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

import { WebWorkerMLCEngine } from '@mlc-ai/web-llm';
import type {
  ChatCompletionMessageParam,
  ChatCompletionTool,
  ChatCompletionMessageToolCall,
} from '@mlc-ai/web-llm';

export type ToolCallHandler = (toolName: string, toolCallId: string, args: Record<string, unknown>) => Promise<string>;

export class BrowserLlmEngine {
  private worker: Worker | null = null;
  private engine: WebWorkerMLCEngine | null = null;
  private currentModelId: string | null = null;

  async ensureLoaded(modelId: string, onProgress: (text: string) => void): Promise<void> {
    if (this.engine !== null && this.currentModelId === modelId) {
      return;
    }
    if (this.engine === null) {
      this.worker = new Worker(new URL('./worker.js', import.meta.url), { type: 'module' });
      this.engine = new WebWorkerMLCEngine(this.worker);
    }
    this.engine.setInitProgressCallback((report) => onProgress(report.text));
    await this.engine.reload(modelId);
    this.currentModelId = modelId;
  }

  async chat(
    messages: ChatCompletionMessageParam[],
    tools: ChatCompletionTool[],
    onToolCall: ToolCallHandler,
    suppressThinking: boolean = false,
    responseSchema?: Record<string, unknown>,
  ): Promise<string> {
    if (this.engine === null) {
      throw new Error('BrowserLlmEngine: engine not loaded');
    }

    const conversation: ChatCompletionMessageParam[] = [...messages];
    if (suppressThinking) {
      const lastUserIndex = conversation.findLastIndex(m => m.role === 'user');
      if (lastUserIndex !== -1) {
        const msg = conversation[lastUserIndex];
        if (typeof msg.content === 'string') {
          conversation[lastUserIndex] = { ...msg, content: msg.content + '\n/no_think' };
        }
      }
    }

    const requestTools = tools.length > 0 ? tools : undefined;
    const responseFormat = responseSchema != null
      ? { type: 'json_object' as const, schema: JSON.stringify(responseSchema) }
      : undefined;

    for (;;) {
      const response = await this.engine.chat.completions.create({
        messages: conversation,
        tools: requestTools,
        stream: false,
        response_format: responseFormat,
      });

      const choice = response.choices[0];
      const message = choice.message;
      const content = suppressThinking
        ? (message.content ?? '').replace(/<think>[\s\S]*?<\/think>/g, '').trim()
        : (message.content ?? '');
      conversation.push({ role: 'assistant', content, tool_calls: message.tool_calls });

      if (choice.finish_reason !== 'tool_calls' || !message.tool_calls || message.tool_calls.length === 0) {
        return content;
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
