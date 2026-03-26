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

namespace TYPO3\CMS\Assist\AI\Assistant;

use TYPO3\CMS\Assist\AI\Agent\AgentCallRequest;
use TYPO3\CMS\Assist\AI\Message\AgentInput;
use TYPO3\CMS\Assist\AI\Message\AgentOutput;

interface AssistantInterface
{
    /**
     * Declare which tools should be available for the agent call.
     *
     * Return null if no tools are needed.
     */
    public function getToolPolicy(): ?ToolPolicy;

    /**
     * Return the system prompt for this assistant, or null if no system message is needed.
     * Applied automatically by {@see AssistantOrchestrator} before the agent call.
     */
    public function getSystemPrompt(): ?string;

    /**
     * Prepare the agent call. The assistant decides messages and tools.
     * System prompts are declared via {@see getSystemPrompt()}.
     *
     * Return null if no remote call is needed (e.g. canned response written directly to $output).
     */
    public function buildAgentCall(AgentInput $input, AgentOutput $output): ?AgentCallRequest;

    /**
     * Post-process the result locally after the remote agent call has completed.
     *
     * Called only when {@see buildAgentCall()} returned a non-null request and
     * the result has been added to $output. Handlers can use this to transform,
     * enrich, or act on the response.
     */
    public function process(AgentInput $input, AgentOutput $output): void;

    public function handleClientRequest(AssistantRequest $request): AssistantResponse;
}
