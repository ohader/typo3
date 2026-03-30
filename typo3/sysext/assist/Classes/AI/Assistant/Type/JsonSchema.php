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

namespace TYPO3\CMS\Assist\AI\Assistant\Type;

/**
 * Thin DTO wrapping a raw JSON schema array.
 *
 * @internal
 */
final readonly class JsonSchema implements \JsonSerializable, \Stringable
{
    public function __construct(public array $data) {}

    public function jsonSerialize(): array
    {
        return $this->data;
    }

    public function __toString(): string
    {
        return json_encode($this->data, JSON_THROW_ON_ERROR);
    }

    /** Returns a copy with the given top-level keys merged over the existing data. */
    public function with(array $overrides): static
    {
        return new static(array_replace($this->data, $overrides));
    }
}
