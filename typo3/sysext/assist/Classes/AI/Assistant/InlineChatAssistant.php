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
use TYPO3\CMS\Assist\AI\Message\AgentInputInterface;
use TYPO3\CMS\Assist\AI\Message\AgentOutputInterface;
use TYPO3\CMS\Assist\AI\Tool\FetchPageRecords;
use TYPO3\CMS\Assist\Attribute\AsAssistant;
use TYPO3\CMS\Assist\Domain\Enum\AssistantCapability;
use TYPO3\CMS\Assist\Domain\Enum\AssistantMode;

#[AsAssistant(
    identifier: 'typo3-assist-inline-chat',
    mode: AssistantMode::inline,
    capabilities: [AssistantCapability::messages, AssistantCapability::toolCalling],
    triggerResources: ['pages', 'tt_content'],
    triggerRoutes: ['/module/web/layout'],
    labelDomain: 'assist.assistants.inline_chat',
)]
final readonly class InlineChatAssistant implements AssistantInterface
{
    public function getToolPolicy(): ToolPolicy
    {
        return new StaticToolPolicy([FetchPageRecords::class]);
    }

    public function buildAgentCall(AgentInputInterface $input, AgentOutputInterface $output): ?AgentCallRequest
    {
        return new AgentCallRequest(
            model: $input->getModel(),
            messageBag: $input->getMessageBag(),
        );
    }

    public function process(AgentInputInterface $input, AgentOutputInterface $output): void {}

    public function handleClientRequest(AssistantRequest $request): AssistantResponse
    {
        return new AssistantResponse();
    }
}
