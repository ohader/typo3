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

use Symfony\AI\Agent\InputProcessorInterface;
use Symfony\AI\Agent\OutputProcessorInterface;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use TYPO3\CMS\Assist\AI\Message\ModelOptions;
use TYPO3\CMS\Assist\AI\Platform\PlatformModel;
use TYPO3\CMS\Assist\Domain\Model\Progress;

/**
 * Fully prepared, ready-to-send request for {@see AgentService}.
 *
 * Built by the assistant handler — the handler decides what messages,
 * tools, and processors to include.
 *
 * @internal
 */
final readonly class AgentCallRequest
{
    /** @var InputProcessorInterface[] */
    public array $inputProcessors;
    /** @var OutputProcessorInterface[] */
    public array $outputProcessors;

    /**
     * @param list<InputProcessorInterface> $inputProcessors
     * @param list<OutputProcessorInterface> $outputProcessors
     */
    public function __construct(
        public PlatformModel $model,
        public MessageBag $messageBag,
        array $inputProcessors = [],
        array $outputProcessors = [],
        public ?Progress $progress = null,
        public ?SequencePointer $sequencePointer = null,
        public ?ModelOptions $modelOptions = null,
    ) {
        $this->inputProcessors = $inputProcessors;
        $this->outputProcessors = $outputProcessors;
    }

    public function withSystemMessage(string $prompt): self
    {
        return new self(
            model: $this->model,
            messageBag: $this->messageBag->withSystemMessage(Message::forSystem($prompt)),
            inputProcessors: $this->inputProcessors,
            outputProcessors: $this->outputProcessors,
            progress: $this->progress,
            sequencePointer: $this->sequencePointer,
            modelOptions: $this->modelOptions,
        );
    }

    /**
     * @param list<InputProcessorInterface> $inputProcessors
     * @param list<OutputProcessorInterface> $outputProcessors
     */
    public function withProcessors(array $inputProcessors, array $outputProcessors): self
    {
        return new self(
            model: $this->model,
            messageBag: $this->messageBag,
            inputProcessors: [...$this->inputProcessors, ...$inputProcessors],
            outputProcessors: [...$this->outputProcessors, ...$outputProcessors],
            progress: $this->progress,
            sequencePointer: $this->sequencePointer,
            modelOptions: $this->modelOptions,
        );
    }
}
