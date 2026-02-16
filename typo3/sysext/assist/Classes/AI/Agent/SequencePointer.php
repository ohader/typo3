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

/**
 * Mutable, forward-only position tracker within {@see Progress::$items}.
 *
 * Corresponds to the `sequence` column in the database.
 *
 * @internal
 */
final class SequencePointer
{
    public function __construct(
        private int $submitted = 0,
        private int $processed = 0,
    ) {}

    public function submitted(): int
    {
        return $this->submitted;
    }

    public function advanceSubmitted(): int
    {
        return ++$this->submitted;

    }

    public function advanceSubmittedTo(int $position): void
    {
        if ($position < $this->submitted()) {
            throw new \LogicException(
                sprintf('Cannot rewind SequencePointer from %d to %d.', $this->submitted, $position),
                1771263824,
            );
        }
        $this->submitted = $position;
    }

    /**
     * Last persisted sequence position.
     */
    public function processed(): int
    {
        return $this->processed;
    }

    public function advanceProcessed(): int
    {
        return ++$this->processed;
    }

    public function advanceProcessedTo(int $position): void
    {
        if ($position < $this->processed) {
            throw new \LogicException(
                sprintf('Cannot rewind SequencePointer from %d to %d.', $this->processed, $position),
                1771263824,
            );
        }
        $this->processed = $position;
    }
}
