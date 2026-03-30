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
final readonly class StateCollection implements \JsonSerializable
{
    /** @param array<string, string> $state */
    public function __construct(public array $state = []) {}

    public function get(string $key): ?string
    {
        return $this->state[$key] ?? null;
    }

    public function jsonSerialize(): array
    {
        return $this->state;
    }

    public static function fromArray(array $data): self
    {
        return new self(array_filter($data, 'is_string'));
    }
}
