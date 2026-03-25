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

use Psr\Container\ContainerInterface;
use Symfony\AI\Agent\Toolbox\AgentProcessor;
use Symfony\AI\Agent\Toolbox\Toolbox;

/**
 * @internal
 */
final readonly class ToolboxFactory
{
    public function __construct(
        private ContainerInterface $container,
    ) {}

    /**
     * @param list<class-string> $toolClassNames
     */
    public function createToolbox(string ...$toolClassNames): Toolbox
    {
        $tools = array_map(
            fn(string $className) => $this->container->get($className),
            $toolClassNames,
        );
        return new Toolbox($tools);
    }

    /**
     * @param list<class-string> $toolClassNames
     */
    public function createAgentProcessor(string ...$toolClassNames): AgentProcessor
    {
        return new AgentProcessor(
            toolbox: $this->createToolbox(...$toolClassNames),
        );
    }

    /**
     * Creates an {@see AgentProcessor} whose toolbox checks for user approval before
     * executing each tool call. Pass the current request params and the list of
     * session-scoped always-approved tool names.
     *
     * @param array<string, mixed> $requestParams
     * @param list<string>         $alwaysApprovedTools
     * @param list<class-string>   $toolClassNames
     */
    public function createApprovingAgentProcessor(
        array $requestParams,
        array $alwaysApprovedTools,
        string ...$toolClassNames,
    ): AgentProcessor {
        $inner = $this->createToolbox(...$toolClassNames);
        $approving = new ApprovingToolbox($inner, $requestParams, $alwaysApprovedTools);
        return new AgentProcessor(toolbox: $approving);
    }
}
