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

namespace TYPO3\CMS\Assist\Controller\Ajax;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\AI\Agent\Toolbox\ToolResultConverter;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Platform\Result\ToolCall;
use TYPO3\CMS\Assist\AI\Agent\ToolboxFactory;
use TYPO3\CMS\Assist\AI\Assistant\AssistantOrchestrator;
use TYPO3\CMS\Assist\AI\Message\AgentInput;
use TYPO3\CMS\Assist\AI\Platform\PlatformModel;
use TYPO3\CMS\Assist\Service\AssistantRegistry;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Core\Http\JsonResponse;

/**
 * Executes a single tool call requested by the browser-side LLM inference engine.
 *
 * Called from the TypeScript `tool-executor.ts` when @mlc-ai/web-llm requests a tool call
 * during in-browser inference. Uses the same tool infrastructure as the server-side agent.
 *
 * @internal
 */
#[AsController]
final readonly class BrowserToolAjaxController
{
    use AssertJsonContentTypeTrait;

    public function __construct(
        private AssistantRegistry $assistantRegistry,
        private AssistantOrchestrator $orchestrator,
        private ToolboxFactory $toolboxFactory,
    ) {}

    /**
     * Execute a tool call from the browser LLM.
     *
     * Request body: { "assistant": "...", "toolName": "...", "toolCallId": "...", "arguments": {} }
     * Response:     { "toolCallId": "...", "result": "..." }
     */
    public function executeTool(ServerRequestInterface $request): ResponseInterface
    {
        if ($response = $this->assertJsonContentType($request)) {
            return $response;
        }

        $body = json_decode((string)$request->getBody(), true) ?? [];
        $assistantIdentifier = (string)($body['assistant'] ?? '');
        $toolName = (string)($body['toolName'] ?? '');
        $toolCallId = (string)($body['toolCallId'] ?? '');
        $arguments = is_array($body['arguments'] ?? null) ? $body['arguments'] : [];

        if ($assistantIdentifier === '' || $toolName === '' || $toolCallId === '') {
            return new JsonResponse(['error' => 'Missing required fields: assistant, toolName, toolCallId'], 400);
        }

        if (!$this->assistantRegistry->hasAssistant($assistantIdentifier)) {
            return new JsonResponse(['error' => sprintf('Unknown assistant "%s".', $assistantIdentifier)], 404);
        }

        $assistant = $this->assistantRegistry->getAssistant($assistantIdentifier);
        $handler = $this->orchestrator->buildHandler($assistant);

        $policy = $handler->getToolPolicy();
        if ($policy === null) {
            return new JsonResponse(['error' => 'This assistant has no tool policy.'], 400);
        }

        // Build a minimal AgentInput for resolveTools(); StaticToolPolicy ignores it
        $dummyModel = new PlatformModel('typo3/symfony-ai-browser-platform', 'browser');
        $dummyInput = new AgentInput($dummyModel, new MessageBag());

        $toolClassNames = $policy->resolveTools($assistant, $dummyInput);
        if ($toolClassNames === []) {
            return new JsonResponse(['error' => 'No tools available for this assistant.'], 400);
        }

        $toolbox = $this->toolboxFactory->createToolbox(...$toolClassNames);
        $toolCall = new ToolCall(id: $toolCallId, name: $toolName, arguments: $arguments);

        $toolResult = $toolbox->execute($toolCall);

        $converter = new ToolResultConverter();
        $resultString = $converter->convert($toolResult) ?? '';

        return new JsonResponse([
            'toolCallId' => $toolCallId,
            'result' => $resultString,
        ]);
    }
}
