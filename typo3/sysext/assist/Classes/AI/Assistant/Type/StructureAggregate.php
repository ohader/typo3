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

use TYPO3\CMS\Core\Schema\Field\FieldTypeInterface;
use TYPO3\CMS\Core\Schema\Field\NumberFieldType;
use TYPO3\CMS\Core\Schema\TcaSchema;

/**
 * @internal
 */
final readonly class StructureAggregate implements AggregateInterface, \Stringable
{
    /**
     * @param PropertyDefinition[] $properties
     */
    public function __construct(
        private array $properties = [],
        public array $value = [],
    ) {}

    public function __toString(): string
    {
        return (string)$this->toJsonSchema();
    }

    /**
     * Creates a schema descriptor from explicit property definitions.
     * Passing zero properties keeps the generic-object behaviour.
     */
    public static function fromDefinition(PropertyDefinition ...$properties): self
    {
        return new self(array_values($properties));
    }

    /**
     * Creates a schema descriptor from a TCA schema.
     * String arguments are resolved as field names; PropertyDefinition instances are used as-is.
     */
    public static function fromTcaSchema(TcaSchema $schema, string|PropertyDefinition ...$properties): self
    {
        if ($properties === []) {
            throw new \LogicException('At least one property must be defined.', 1773356650);
        }
        $defs = [];
        foreach ($properties as $prop) {
            $definition = $prop instanceof PropertyDefinition ? $prop : null;
            $field = $schema->getField($definition->name ?? $prop);
            $defs[] = new PropertyDefinition(
                name: $definition->name ?? $prop,
                type: $definition->type ?? self::mapTcaTypeToJsonType($field),
                comment: $definition->comment ?? ($field->getLabel() !== '' ? $field->getLabel() : null),
            );
        }
        return new self($defs);
    }

    public function getDiscriminator(): string
    {
        return 'structure';
    }

    public function toJsonSchema(): JsonSchema
    {
        if ($this->properties === []) {
            return new JsonSchema([
                'type' => 'object',
                'properties' => [
                    'type' => ['type' => 'string', 'const' => 'structure'],
                    'value' => [
                        'type' => 'object',
                        '$comment' => 'Structured JSON of the actual arbitrary data.',
                    ],
                ],
                'required' => ['type', 'value'],
                'additionalProperties' => false,
            ]);
        }

        $valueProperties = [];
        $required = [];
        foreach ($this->properties as $def) {
            $prop = ['type' => $def->type];
            if ($def->comment !== null) {
                $prop['$comment'] = $def->comment;
            }
            $valueProperties[$def->name] = $prop;
            $required[] = $def->name;
        }

        return new JsonSchema([
            'type' => 'object',
            'properties' => [
                'type' => ['type' => 'string', 'const' => 'structure'],
                'value' => [
                    'type' => 'object',
                    'properties' => $valueProperties,
                    'required' => $required,
                    'additionalProperties' => false,
                ],
            ],
            'required' => ['type', 'value'],
            'additionalProperties' => false,
        ]);
    }

    public function parse(array $json): static
    {
        return new static($this->properties, $json['value']);
    }

    private static function mapTcaTypeToJsonType(FieldTypeInterface $field): string
    {
        return match ($field->getType()) {
            'check' => 'boolean',
            'number' => $field instanceof NumberFieldType && $field->getFormat() === 'integer' ? 'integer' : 'number',
            default => 'string',
        };
    }
}
