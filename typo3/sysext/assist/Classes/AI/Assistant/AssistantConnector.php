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
use TYPO3\CMS\Assist\AI\Message\AgentInputInterface;
use TYPO3\CMS\Assist\AI\Message\AgentOutputInterface;
use TYPO3\CMS\Assist\Domain\Model\Assistant;

/**
 * Resolves the handler for an {@see Assistant} and delegates processing
 * to it via the {@see AssistantInterface} contract.
 *
 * @internal
 */
final readonly class AssistantConnector
{
    public function __construct(
        private ContainerInterface $container,
    ) {}

    public function process(Assistant $assistant, AgentInputInterface $input, AgentOutputInterface $output): void
    {
        $handler = $this->container->get($assistant->handler);
        if (!$handler instanceof AssistantInterface) {
            throw new \RuntimeException(
                sprintf(
                    'Handler "%s" for assistant "%s" must implement %s.',
                    $assistant->handler,
                    $assistant->identifier,
                    AssistantInterface::class,
                ),
                1771200003,
            );
        }
        $handler->process($input, $output);
    }
}
