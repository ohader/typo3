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
 * Schema combinator that applies all listed schemas simultaneously (allOf).
 *
 * @internal
 */
final readonly class IntersectionAggregate implements \Stringable
{
    /**
     * @param class-string<TypeInterface>|AggregateInterface ...$types Class-strings implementing TypeInterface, or AggregateInterface instances
     */
    public static function of(string|AggregateInterface ...$types): self
    {
        return new self(array_values($types));
    }

    /**
     * @param array<string|AggregateInterface> $types
     * @param array<TypeInterface|AggregateInterface> $parts
     */
    private function __construct(
        private array $types,
        public array $parts = [],
    ) {}

    public function __toString(): string
    {
        return (string)$this->toJsonSchema();
    }

    /** Returns {"allOf": [...]} */
    public function toJsonSchema(): JsonSchema
    {
        return new JsonSchema(['allOf' => array_map(
            fn(string|AggregateInterface $type) => $this->isStaticType($type)
                ? $type::toJsonSchema()->jsonSerialize()
                : $type->toJsonSchema()->jsonSerialize(),
            $this->types,
        )]);
    }

    /**
     * Applies each component's parser to $json and returns a new instance with populated $parts.
     */
    public function parse(array $json): static
    {
        $parts = [];
        foreach ($this->types as $type) {
            $parts[] = $this->isStaticType($type) ? $type::fromJson($json) : $type->parse($json);
        }
        return new static($this->types, $parts);
    }

    private function isStaticType(mixed $type): bool
    {
        return is_string($type) && is_a($type, TypeInterface::class, true);
    }
}
