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
 * Value object describing a single property in a structured AI response.
 *
 * @internal
 */
final readonly class PropertyDefinition
{
    public function __construct(
        public string $name,
        public ?string $type = null, // JSON Schema primitive: "string", "integer", "number", "boolean"
        public ?string $comment = null, // maps to JSON Schema $comment
    ) {}
}
