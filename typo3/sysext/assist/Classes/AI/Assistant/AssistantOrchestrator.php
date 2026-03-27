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

use Psr\Container\ContainerInterface;
use Symfony\AI\Platform\Message\AssistantMessage;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Platform\Message\SystemMessage;
use Symfony\AI\Platform\Message\UserMessage;
use Symfony\AI\Platform\Tool\Tool;
use TYPO3\CMS\Assist\AI\Agent\AgentCallRequest;
use TYPO3\CMS\Assist\AI\Agent\AgentService;
use TYPO3\CMS\Assist\AI\Agent\BrowserDelegationResult;
use TYPO3\CMS\Assist\AI\Agent\ToolboxFactory;
use TYPO3\CMS\Assist\AI\Message\AgentInput;
use TYPO3\CMS\Assist\AI\Message\AgentOutput;
use TYPO3\CMS\Assist\Domain\Model\Assistant;

/**
 * Resolves the handler for an {@see Assistant} and delegates processing
 * to it via the {@see AssistantInterface} contract.
 *
 * @internal
 */
final readonly class AssistantOrchestrator
{
    public function __construct(
        private AgentService $agentService,
        private ToolboxFactory $toolboxFactory,
        private ContainerInterface $container,
    ) {}

    /**
     * @param array<string, mixed> $requestParams  Current request params forwarded from AssistantRequest::$params.
     *                                             Used by the approving toolbox to check tool-call approvals.
     */
    public function process(
        AssistantInterface $handler,
        AgentInput $input,
        AgentOutput $output,
        array $requestParams = [],
    ): void {
        $assistant = $handler->getAssistant();
        $request = $handler->buildAgentCall($input, $output);
        if ($request === null) {
            return;
        }

        $systemPrompt = $handler->getSystemPrompt();
        if ($systemPrompt !== null) {
            $request = $request->withSystemMessage($systemPrompt);
        }

        $request = $this->withToolPolicy($handler, $assistant, $input, $request, $requestParams);

        if ($request->model->isLocal) {
            $responseSchema = $handler instanceof BrowserResponseSchemaProvider
                ? $handler->getBrowserResponseSchema()
                : null;
            $output->add(new BrowserDelegationResult(
                assistantIdentifier: $assistant->identifier,
                model: $request->model->model,
                messages: $this->serializeMessageBag($request->messageBag),
                tools: $this->extractBrowserToolSchemas($handler, $assistant, $input),
                suppressThinking: $request->model->suppressThinking,
                responseSchema: $responseSchema,
            ));
            return;
        }

        $result = $this->agentService->call($request);
        $output->add($result);
    }

    public function buildHandler(Assistant $assistant): AssistantInterface
    {
        $handler = $this->container->get($assistant->handler);
        if ($handler instanceof AssistantInterface) {
            return $handler;
        }
        throw new \RuntimeException(
            sprintf(
                'Handler "%s" for assistant "%s" must implement %s.',
                $assistant->handler,
                $assistant->identifier,
                AssistantInterface::class,
            ),
            1771318317,
        );
    }

    private function withToolPolicy(
        AssistantInterface $handler,
        Assistant $assistant,
        AgentInput $input,
        AgentCallRequest $request,
        array $requestParams,
    ): AgentCallRequest {
        $policy = $handler->getToolPolicy();
        if ($policy === null) {
            return $request;
        }
        $tools = $policy->resolveTools($assistant, $input);
        if ($tools === []) {
            return $request;
        }
        $alwaysApproved = json_decode($requestParams['assistToolApprovals'] ?? '[]', true) ?? [];
        $agentProcessor = $this->toolboxFactory->createApprovingAgentProcessor(
            $requestParams,
            $alwaysApproved,
            ...$tools,
        );
        return $request->withProcessors([$agentProcessor], [$agentProcessor]);
    }

    /**
     * Extracts tool definitions from the handler's tool policy in OpenAI JSON schema format,
     * for use in browser-side inference via @mlc-ai/web-llm.
     *
     * @return list<array<string, mixed>>
     */
    private function extractBrowserToolSchemas(AssistantInterface $handler, Assistant $assistant, AgentInput $input): array
    {
        $policy = $handler->getToolPolicy();
        if ($policy === null) {
            return [];
        }
        $toolClassNames = $policy->resolveTools($assistant, $input);
        if ($toolClassNames === []) {
            return [];
        }
        $toolbox = $this->toolboxFactory->createToolbox(...$toolClassNames);
        return array_map($this->serializeTool(...), $toolbox->getTools());
    }

    /**
     * @return array{type: string, function: array{name: string, description: string, parameters: mixed}}
     */
    private function serializeTool(Tool $tool): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $tool->getName(),
                'description' => $tool->getDescription(),
                'parameters' => $tool->getParameters() ?? ['type' => 'object', 'properties' => new \stdClass()],
            ],
        ];
    }

    /**
     * Serializes a MessageBag to OpenAI-format role/content pairs for the browser LLM.
     * Only system, user, and assistant messages are included; tool-call messages are skipped.
     *
     * @return list<array{role: string, content: string}>
     */
    private function serializeMessageBag(MessageBag $messageBag): array
    {
        $messages = [];
        foreach ($messageBag->getMessages() as $message) {
            $role = $message->getRole()->value;
            if ($message instanceof SystemMessage) {
                $messages[] = ['role' => $role, 'content' => (string)$message->getContent()];
            } elseif ($message instanceof UserMessage) {
                $messages[] = ['role' => $role, 'content' => $message->asText() ?? ''];
            } elseif ($message instanceof AssistantMessage) {
                $messages[] = ['role' => $role, 'content' => $message->getContent() ?? ''];
            }
        }
        return $messages;
    }
}
