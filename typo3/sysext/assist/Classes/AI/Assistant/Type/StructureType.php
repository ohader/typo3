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
 * @internal
 */
final readonly class StructureType implements TypeInterface
{
    public function __construct(public array $value) {}

    public static function getType(): string
    {
        return 'structure';
    }

    public static function toJsonSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'type' => ['type' => 'string', 'const' => 'structure'],
                'value' => [
                    'type' => 'object',
                    '$comment' => 'Structured JSON of the actual arbitrary data.'
                ],
            ],
            'required' => ['type', 'value'],
            'additionalProperties' => false,
        ];
    }

    public static function fromJson(array $json): static
    {
        return new static($json['value']);
    }
}
