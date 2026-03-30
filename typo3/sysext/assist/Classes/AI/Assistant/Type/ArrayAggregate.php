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
 * Composite descriptor/result for a plain JSON array of typed items.
 *
 * Unlike ListAggregate, the AI returns a bare JSON array ([item1, item2, …])
 * with no envelope object or type discriminator. This is ideal when the
 * response is always an array and you do not need union dispatch.
 *
 * @internal
 */
final readonly class ArrayAggregate implements AggregateInterface, \Stringable
{
    /**
     * @param class-string<TypeInterface>|UnionAggregate|AggregateInterface $itemType Class-string implementing TypeInterface, a UnionAggregate, or an AggregateInterface instance
     * @param array $items Populated after parse()
     */
    public function __construct(
        private string|UnionAggregate|AggregateInterface $itemType,
        public array $items = [],
    ) {}

    public function __toString(): string
    {
        return (string)$this->toJsonSchema();
    }

    public static function of(string|UnionAggregate|AggregateInterface $itemType): self
    {
        return new self($itemType);
    }

    public function getDiscriminator(): string
    {
        return 'array';
    }

    public function toJsonSchema(): JsonSchema
    {
        $itemsSchema = $this->isStaticType()
            ? ($this->itemType)::toJsonSchema()->jsonSerialize()
            : $this->itemType->toJsonSchema()->jsonSerialize();

        return new JsonSchema([
            'type' => 'array',
            'items' => $itemsSchema,
        ]);
    }

    public function parse(array $json): static
    {
        $parsedItems = array_map(
            fn(array $item) => $this->itemType instanceof UnionAggregate
                ? $this->itemType->parse($item)
                : ($this->itemType instanceof AggregateInterface
                    ? $this->itemType->parse($item)
                    : ($this->itemType)::fromJson($item)),
            $json,
        );

        return new static($this->itemType, $parsedItems);
    }

    private function isStaticType(): bool
    {
        return is_string($this->itemType) && is_a($this->itemType, TypeInterface::class, true);
    }
}
