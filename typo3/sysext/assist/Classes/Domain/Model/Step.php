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
final readonly class Step implements \JsonSerializable
{
    /**
     * @param string $identifier Unique identifier of this step
     * @param string $description Human-readable description of this step
     * @param string $subject Internal references to subjects (resources), @todo might be a separate type
     * @param array $subs Sub-steps (children) of the current step
     * @param bool $done Whether this step is done or not
     */
    public function __construct(
        public string $identifier,
        public string $description,
        public string $subject,
        public array $subs = [],
        public bool $done = false,
    ) {}

    public function markDone(): self
    {
        return new self(
            $this->identifier,
            $this->description,
            $this->subject,
            $this->subs,
            true
        );
    }

    public function areSubsDone(): bool
    {
        $unfinishedSubs = array_filter(
            $this->subs,
            static fn(Step $step) => !$step->areSubsDone()
        );
        return $unfinishedSubs === [];
    }

    public function jsonSerialize(): array
    {
        return [
            'identifier' => $this->identifier,
            'description' => $this->description,
            'subject' => $this->subject,
            'subs' => $this->subs,
            'done' => $this->done,
        ];
    }
}
