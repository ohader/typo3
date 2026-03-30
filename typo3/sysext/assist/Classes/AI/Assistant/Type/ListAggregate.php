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
 * Composite descriptor/result for a list of typed items.
 *
 * @internal
 */
final readonly class ListAggregate implements AggregateInterface, \Stringable
{
    /**
     * @param class-string<TypeInterface>|UnionAggregate|IntersectionAggregate|AggregateInterface $itemType Class-string implementing TypeInterface, a UnionAggregate, an IntersectionAggregate, or an AggregateInterface instance
     * @param AggregateInterface[] $items Populated after parse()
     */
    public function __construct(
        private string|UnionAggregate|IntersectionAggregate|AggregateInterface $itemType,
        public array $items = [],
    ) {}

    public function __toString(): string
    {
        return (string)$this->toJsonSchema();
    }

    public static function of(string|UnionAggregate|IntersectionAggregate|AggregateInterface $itemType): self
    {
        return new self($itemType);
    }

    public function getDiscriminator(): string
    {
        return 'list';
    }

    public function toJsonSchema(): JsonSchema
    {
        $itemsSchema = $this->isStaticType()
            ? ($this->itemType)::toJsonSchema()->jsonSerialize()
            : $this->itemType->toJsonSchema()->jsonSerialize();

        return new JsonSchema([
            'type' => 'object',
            '$comment' => 'Use this type when presenting a list or enumeration of items.',
            'properties' => [
                'type' => ['type' => 'string', 'const' => 'list'],
                'items' => [
                    'type' => 'array',
                    'items' => $itemsSchema,
                ],
            ],
            'required' => ['type', 'items'],
            'additionalProperties' => false,
        ]);
    }

    public function parse(array $json): static
    {
        $parsedItems = array_map(
            fn(array $item) => $this->isStaticType()
                ? ($this->itemType)::fromJson($item)
                : $this->itemType->parse($item),
            $json['items'],
        );

        return new static($this->itemType, $parsedItems);
    }

    private function isStaticType(): bool
    {
        return is_string($this->itemType) && is_a($this->itemType, TypeInterface::class, true);
    }
}
