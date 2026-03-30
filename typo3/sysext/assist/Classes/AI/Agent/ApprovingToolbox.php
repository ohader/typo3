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

use Symfony\AI\Agent\Toolbox\ToolboxInterface;
use Symfony\AI\Agent\Toolbox\ToolResult;
use Symfony\AI\Platform\Result\ToolCall;
use TYPO3\CMS\Assist\Exception\ToolApprovalRequiredException;

/**
 * Decorator around {@see ToolboxInterface} that gates each tool execution behind
 * user approval. Before calling the inner toolbox, it checks whether the current
 * request carries an approval for the requested tool.
 *
 * Approval key in request params: {@see self::PARAM_PREFIX}{toolName}
 * Accepted values: "approve" (single-use) or "always-approve" (session-scoped).
 *
 * Always-approved tools for the session are passed via the constructor as
 * $alwaysApprovedTools, derived from the "assistToolApprovals" state param.
 *
 * If no approval is found, {@see ToolApprovalRequiredException} is thrown,
 * which propagates to the assistant handler to return a ToolApprovalFeedback.
 */
final class ApprovingToolbox implements ToolboxInterface
{
    public const PARAM_PREFIX = 'tool_approval__';

    /**
     * @param array<string, mixed> $requestParams    Current request params (AssistantRequest::$params).
     * @param list<string>         $alwaysApprovedTools  Tool names approved for the entire session.
     */
    public function __construct(
        private readonly ToolboxInterface $inner,
        private readonly array $requestParams,
        private readonly array $alwaysApprovedTools = [],
    ) {}

    public function getTools(): array
    {
        return $this->inner->getTools();
    }

    public function execute(ToolCall $toolCall): ToolResult
    {
        $toolName = $toolCall->getName();

        if (in_array($toolName, $this->alwaysApprovedTools, true)) {
            return $this->inner->execute($toolCall);
        }

        $approval = $this->requestParams[self::PARAM_PREFIX . $toolName] ?? null;
        if ($approval === 'approve' || $approval === 'always-approve') {
            return $this->inner->execute($toolCall);
        }

        throw new ToolApprovalRequiredException($toolName, $toolCall->getArguments());
    }
}
