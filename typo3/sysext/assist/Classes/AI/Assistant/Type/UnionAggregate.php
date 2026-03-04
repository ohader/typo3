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
 * Schema combinator for a discriminated union of StaticTypeInterface and AggregateInterface types.
 *
 * @internal
 */
final class UnionAggregate
{
    /**
     * @param AggregateInterface[] $types
     */
    private function __construct(private readonly array $types) {}

    /**
     * @param class-string<TypeInterface>|AggregateInterface ...$types Class-strings implementing StaticTypeInterface, or AggregateInterface instances
     */
    public static function of(string|AggregateInterface ...$types): self
    {
        return new self(array_values($types));
    }

    /** Returns {"oneOf": [...]} */
    public function toJsonSchema(): array
    {
        return ['oneOf' => array_map(
            fn(string|AggregateInterface $type) => $this->isStaticType($type)
                ? $type::toJsonSchema()
                : $type->toJsonSchema(),
            $this->types,
        )];
    }

    /**
     * Dispatches $json to the matching type via getType() or getDiscriminator().
     *
     * @throws \UnexpectedValueException For unknown type discriminator (code 1740999601)
     */
    public function parse(array $json): TypeInterface|AggregateInterface
    {
        $discriminator = $json['type'] ?? '';
        foreach ($this->types as $type) {
            $typeKey = $this->isStaticType($type) ? $type::getType() : $type->getDiscriminator();
            if ($typeKey === $discriminator) {
                return $this->isStaticType($type) ? $type::fromJson($json) : $type->parse($json);
            }
        }
        throw new \UnexpectedValueException(
            sprintf('Unknown output format type "%s".', $discriminator),
            1740999601,
        );
    }

    public function __toString(): string
    {
        return json_encode($this->toJsonSchema(), JSON_THROW_ON_ERROR);
    }

    private function isStaticType(mixed $type): bool
    {
        return is_string($type) && is_a($type, TypeInterface::class, true);
    }
}
