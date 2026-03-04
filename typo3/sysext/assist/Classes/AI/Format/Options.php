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
 * Composite descriptor/result for a selection of at least two typed options.
 *
 * @internal
 */
final readonly class Options
{
    /**
     * @param string|UnionType $itemType Class-string implementing OutputFormatInterface, or a UnionType
     * @param list<OutputFormatInterface> $items Populated after parse()
     */
    public function __construct(
        private string|UnionType $itemType,
        public array $items = [],
    ) {}

    public function toJsonSchema(): array
    {
        $itemsSchema = $this->itemType instanceof UnionType
            ? $this->itemType->toJsonSchema()
            : ($this->itemType)::toJsonSchema();

        return [
            'type' => 'object',
            'properties' => [
                'type' => ['type' => 'string', 'const' => 'options'],
                'items' => [
                    'type' => 'array',
                    'items' => $itemsSchema,
                    'minItems' => 2,
                ],
            ],
            'required' => ['type', 'items'],
            'additionalProperties' => false,
        ];
    }

    public function parse(array $json): self
    {
        $parsedItems = array_map(
            fn(array $item) => $this->itemType instanceof UnionType
                ? $this->itemType->parse($item)
                : ($this->itemType)::fromJson($item),
            $json['items'],
        );

        return new self($this->itemType, $parsedItems);
    }
}
