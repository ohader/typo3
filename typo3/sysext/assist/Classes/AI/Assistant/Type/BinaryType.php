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
final readonly class BinaryType implements TypeInterface
{
    public function __construct(
        public string $data,
        public ?string $mimeType = null,
    ) {}

    public static function getType(): string
    {
        return 'binary';
    }

    public static function toJsonSchema(): array
    {
        return [
            'type' => 'object',
            '$comment' => 'Use this type to provide base64-encoded binary data.',
            'properties' => [
                'type' => ['type' => 'string', 'const' => 'binary'],
                'data' => ['type' => 'string', 'contentEncoding' => 'base64'],
                'mimeType' => ['type' => 'string'],
            ],
            'required' => ['type', 'data'],
            'additionalProperties' => false,
        ];
    }

    public static function fromJson(array $json): static
    {
        return new static($json['data'], $json['mimeType'] ?? null);
    }
}
