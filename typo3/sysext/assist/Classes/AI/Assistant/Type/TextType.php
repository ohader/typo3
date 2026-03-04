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
final readonly class TextType implements TypeInterface
{
    public function __construct(public string $value) {}

    public static function getType(): string
    {
        return 'text';
    }

    public static function toJsonSchema(): array
    {
        return [
            'type' => 'object',
            '$comment' => 'Use this type to provide plain text content.',
            'properties' => [
                'type' => [
                    'type' => 'string',
                    'const' => 'text',
                ],
                'value' => [
                    'type' => 'string',
                    '$comment' => 'Only plain text is allowed.',
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
