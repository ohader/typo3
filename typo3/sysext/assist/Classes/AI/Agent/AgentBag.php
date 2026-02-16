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

use Psr\Log\LoggerInterface;
use Symfony\AI\Agent\InputProcessorInterface;
use Symfony\AI\Agent\OutputProcessorInterface;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface as SymfonyEventDispatcherInterface;
use TYPO3\CMS\Assist\AI\Platform\PlatformModel;
use TYPO3\CMS\Assist\Domain\Enum\ProgressItemType;
use TYPO3\CMS\Assist\Domain\Model\Progress;

/**
 * Bridge between TYPO3 {@see Progress} and Symfony AI Agent.
 *
 * @internal
 */
final readonly class AgentBag
{
    /** @var InputProcessorInterface[] */
    public array $inputProcessors;
    /** @var OutputProcessorInterface[] */
    public array $outputProcessors;

    /**
     * @param InputProcessorInterface[] $inputProcessors
     * @param OutputProcessorInterface[] $outputProcessors
     */
    public function __construct(
        public PlatformModel $model,
        public Progress $progress,
        public SequencePointer $sequencePointer,
        array $inputProcessors = [],
        array $outputProcessors = [],
        public ?LoggerInterface $logger = null,
        public ?SymfonyEventDispatcherInterface $symfonyEventDispatcher = null,
    ) {
        $this->inputProcessors = $inputProcessors;
        $this->outputProcessors = $outputProcessors;
    }

    public function toMessageBag(): MessageBag
    {
        $messages = [];
        foreach ($this->progress->items as $item) {
            $messages[] = match ($item->type) {
                ProgressItemType::submitted => Message::ofUser((string)$item->payload),
                ProgressItemType::received => Message::ofAssistant((string)$item->payload),
            };
        }
        return new MessageBag(...$messages);
    }
}
