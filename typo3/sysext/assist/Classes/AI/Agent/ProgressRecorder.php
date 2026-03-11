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

use Symfony\AI\Platform\Result\ResultInterface;
use TYPO3\CMS\Assist\Domain\Enum\ProgressItemType;
use TYPO3\CMS\Assist\Domain\Model\ProgressItem;
use TYPO3\CMS\Assist\Domain\Repository\ProgressRepository;

/**
 * @internal
 */
final readonly class ProgressRecorder implements ProgressRecorderInterface
{
    public function __construct(
        private ProgressRepository $progressRepository,
    ) {}

    public function recordSubmitted(AgentCallRequest $request): void
    {
        $progress = $request->progress;
        $sequencePointer = $request->sequencePointer;
        if ($progress === null || $sequencePointer === null) {
            return;
        }
        $items = $progress->items;
        for ($i = $sequencePointer->submitted(), $end = count($items); $i < $end; $i++) {
            $sequence = $this->progressRepository->appendItem(
                uuid: $progress->uuid,
                item: $items[$i],
            );
            $sequencePointer->advanceProcessedTo($sequence);
        }
    }

    public function recordReceived(AgentCallRequest $request, ResultInterface $result): void
    {
        $progress = $request->progress;
        $sequencePointer = $request->sequencePointer;
        if ($progress === null || $sequencePointer === null) {
            return;
        }
        $sequence = $this->progressRepository->appendItem(
            uuid: $progress->uuid,
            item: new ProgressItem(
                type: ProgressItemType::received,
                payload: $result->getContent(),
            ),
        );
        $sequencePointer->advanceProcessedTo($sequence);
    }
}
