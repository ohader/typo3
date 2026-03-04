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
 * Composite descriptor/result for a selection of at least two typed options.
 * Only options of the same (singular) type are allowed.
 *
 * @internal
 */
final readonly class OptionsAggregate implements AggregateInterface
{
    /**
     * @param class-string<TypeInterface>|UnionAggregate $itemType Class-string implementing StaticTypeInterface, or a UnionAggregate
     * @param AggregateInterface[] $items Populated after parse()
     */
    public function __construct(
        private string|UnionAggregate $itemType,
        public array $items = [],
    ) {}

    public static function of(string|UnionAggregate $itemType): self
    {
        return new self($itemType);
    }

    public function getDiscriminator(): string
    {
        return 'options';
    }

    public function toJsonSchema(): array
    {
        $itemsSchema = $this->isStaticType()
            ? ($this->itemType)::toJsonSchema()
            : $this->itemType->toJsonSchema();

        return [
            'type' => 'object',
            '$comment' => 'Use this type when presenting a list of options the user can choose from.',
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

    public function parse(array $json): static
    {
        $parsedItems = array_map(
            fn(array $item) => $this->itemType instanceof UnionAggregate
                ? $this->itemType->parse($item)
                : ($this->itemType)::fromJson($item),
            $json['items'],
        );

        return new static($this->itemType, $parsedItems);
    }

    private function isStaticType(): bool
    {
        return is_string($this->itemType) && is_a($this->itemType, TypeInterface::class, true);
    }
}
