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
use TYPO3\CMS\Assist\Attribute\AsAssistant;
use TYPO3\CMS\Assist\Domain\Enum\AssistantCapability;
use TYPO3\CMS\Assist\Domain\Model\Assistant;
use TYPO3\CMS\Assist\Service\AssistantRegistry;

#[AsAssistant(
    identifier: self::IDENTIFIER,
    capabilities: [AssistantCapability::messages, AssistantCapability::inputImage, AssistantCapability::toolCalling],
    triggerResources: ['pages'],
    triggerComponents: ['page-tree', 'context-menu'],
    triggerRoutes: ['/module/web/layout'],
    labelDomain: 'assist.assistants.a11y',
    additionalModule: '@typo3/assist/assistant/a11y-assistant',
)]
final readonly class A11yAssistant implements AssistantInterface
{
    private const IDENTIFIER = 'typo3-assist-a11y';

    public function __construct(
        private AssistantRegistry $assistantRegistry,
    ) {}

    public function getAssistant(): Assistant
    {
        return $this->assistantRegistry->getAssistant(self::IDENTIFIER);
    }

    public function getSystemPrompt(): ?string
    {
        return null;
    }

    public function getToolPolicy(): ?ToolPolicy
    {
        return null;
    }

    public function buildAgentCall(AgentInput $input, AgentOutput $output): ?AgentCallRequest
    {
        return new AgentCallRequest(
            model: $input->model,
            messageBag: $input->messageBag,
        );
    }

    public function process(AgentInput $input, AgentOutput $output): void {}

    public function handleClientRequest(AssistantRequest $request): AssistantResponse
    {
        return new AssistantResponse();
    }
}
