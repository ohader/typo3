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
final readonly class Subject implements OutputFormatInterface
{
    public function __construct(public string $value) {}

    public static function getType(): string
    {
        return 'subject';
    }

    public static function toJsonSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'type' => ['type' => 'string', 'const' => 'subject'],
                'value' => [
                    'type' => 'string',
                    'description' => 'JSON-encoded TYPO3 subject: TCA record field or FAL resource',
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
