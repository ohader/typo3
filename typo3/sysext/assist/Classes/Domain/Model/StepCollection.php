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

namespace TYPO3\CMS\Assist\Domain\Model;

/**
 * @internal
 */
class StepCollection implements \JsonSerializable
{
    /** @param list<Step> $steps */
    public array $steps;

    public function __construct(array $steps = [])
    {
        $this->steps = $steps;
    }

    public function find(int $index): ?Step
    {
        return $this->steps[$index] ?? null;
    }

    /**
     * Replaces the step in-collection with its done counterpart; returns the done step.
     */
    public function markDone(Step $step): Step
    {
        $done = $step->markDone();
        foreach ($this->steps as $i => $s) {
            if ($s->identifier === $step->identifier) {
                $this->steps[$i] = $done;
                break;
            }
        }
        return $done;
    }

    /**
     * Returns the first top-level step that is not yet done.
     */
    public function findNext(): ?Step
    {
        foreach ($this->steps as $step) {
            if (!$step->done) {
                return $step;
            }
        }
        return null;
    }

    /**
     * Returns the 0-based index of the step, or null if not found.
     */
    public function index(Step $step): ?int
    {
        foreach ($this->steps as $i => $s) {
            if ($s->identifier === $step->identifier) {
                return $i;
            }
        }
        return null;
    }

    public function jsonSerialize(): array
    {
        return $this->steps;
    }

    /**
     * Reconstruct from a JSON-decoded array of step arrays.
     */
    public static function fromArray(array $items): self
    {
        return new self(array_map(
            static fn(array $d) => self::stepFromArray($d),
            $items,
        ));
    }

    private static function stepFromArray(array $d): Step
    {
        return new Step(
            $d['identifier'],
            $d['description'],
            $d['subject'],
            array_map(static fn(array $sub) => self::stepFromArray($sub), $d['subs'] ?? []),
            (bool)$d['done'],
        );
    }
}
