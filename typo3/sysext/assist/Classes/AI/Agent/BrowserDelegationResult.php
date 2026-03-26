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

namespace TYPO3\CMS\Assist\AI\Agent;

use Symfony\AI\Platform\Result\BaseResult;

/**
 * Signals that inference should be delegated to the user's browser via @mlc-ai/web-llm.
 *
 * Returned by {@see \TYPO3\CMS\Assist\AI\Assistant\AssistantOrchestrator} when the
 * configured platform is `typo3/symfony-ai-browser-platform`. Carries everything the
 * frontend needs to run inference locally:
 *
 * - $assistantIdentifier — used by the tool-execution endpoint to resolve the correct tool policy
 * - $model               — WebLLM model ID (e.g. "Qwen2.5-0.5B-Instruct-q4f16_1-MLC")
 * - $messages            — OpenAI-format role/content pairs (system + new user message)
 * - $tools               — OpenAI tool schema array for the assistant's tool policy
 *
 * @internal
 */
final class BrowserDelegationResult extends BaseResult
{
    /**
     * @param list<array{role: string, content: string}> $messages
     * @param list<array<string, mixed>>                 $tools    OpenAI tool schema objects
     */
    public function __construct(
        public readonly string $assistantIdentifier,
        public readonly string $model,
        public readonly array $messages,
        public readonly array $tools,
    ) {}

    public function getContent(): null
    {
        return null;
    }
}
