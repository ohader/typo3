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
use TYPO3\CMS\Assist\AI\Agent\AgentCallRequest;
use TYPO3\CMS\Assist\AI\Agent\AgentService;
use TYPO3\CMS\Assist\AI\Agent\ToolboxFactory;
use TYPO3\CMS\Assist\AI\Message\AgentInputInterface;
use TYPO3\CMS\Assist\AI\Message\AgentOutputInterface;
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
        Assistant $assistant,
        AgentInputInterface $input,
        AgentOutputInterface $output,
        array $requestParams = [],
    ): void {
        $handler = $this->buildHandler($assistant);
        $handler->process($input, $output);

        $request = $handler->buildAgentCall($input, $output);
        if ($request === null) {
            return;
        }

        $systemPrompt = $handler->getSystemPrompt();
        if ($systemPrompt !== null) {
            $request = $request->withSystemMessage($systemPrompt);
        }

        $request = $this->withToolPolicy($handler, $assistant, $input, $request, $requestParams);

        $result = $this->agentService->call($request);
        $output->add($result);
        $handler->process($input, $output);
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
        AgentInputInterface $input,
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
}
