<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Assist\AI\Assistant\Feedback;

use TYPO3\CMS\Assist\AI\Message\ModelOptions;

/**
 * Instructs the frontend to run inference in the browser via @mlc-ai/web-llm.
 *
 * Produced by {@see ResultConverter} from a {@see \TYPO3\CMS\Assist\AI\Agent\BrowserDelegationResult}.
 *
 * The frontend (chat-element.ts) intercepts this feedback type, loads the WebLLM engine,
 * runs the model locally, and handles tool calls by posting to `assist_browser_execute_tool`.
 */
final readonly class BrowserInferenceFeedback implements FeedbackInterface
{
    /**
     * @param list<array{role: string, content: string}> $messages OpenAI-format messages (system + new user message)
     * @param list<array<string, mixed>> $tools OpenAI tool schema objects
     * @param array<string, mixed>|null $responseSchema JSON Schema for constrained output, or null
     */
    public function __construct(
        public string $assistantIdentifier,
        public string $model,
        public array $messages,
        public array $tools,
        public bool $suppressThinking = false,
        public ?array $responseSchema = null,
        public ?ModelOptions $modelOptions = null,
    ) {}

    public function getValue(): string
    {
        return '';
    }

    public function jsonSerialize(): array
    {
        return [
            'type' => 'browser-inference',
            'assistant' => $this->assistantIdentifier,
            'model' => $this->model,
            'messages' => $this->messages,
            'tools' => $this->tools,
            'suppressThinking' => $this->suppressThinking,
            'responseSchema' => $this->responseSchema,
            'modelOptions' => $this->modelOptions !== null ? [
                'temperature' => $this->modelOptions->temperature,
                'max_tokens' => $this->modelOptions->maxTokens,
                'repetition_penalty' => $this->modelOptions->repetitionPenalty,
            ] : null,
        ];
    }
}
