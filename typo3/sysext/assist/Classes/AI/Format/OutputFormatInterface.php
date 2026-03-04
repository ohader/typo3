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

namespace TYPO3\CMS\Assist\AI\Format;

/**
 * @internal
 */
interface OutputFormatInterface
{
    public static function getType(): string;

    /** Returns the JSON schema array describing this format */
    public static function toJsonSchema(): array;

    /** Constructs an instance from a decoded JSON array */
    public static function fromJson(array $json): static;
}
