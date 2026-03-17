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

namespace TYPO3\CMS\Assist\Tests\Unit\AI\Assistant\Type;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Assist\AI\Assistant\Type\PropertyDefinition;
use TYPO3\CMS\Assist\AI\Assistant\Type\StructureAggregate;
use TYPO3\CMS\Core\Schema\Field\FieldTypeInterface;
use TYPO3\CMS\Core\Schema\Field\NumberFieldType;
use TYPO3\CMS\Core\Schema\TcaSchema;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class StructureAggregateTest extends UnitTestCase
{
    // -------------------------------------------------------------------------
    // getDiscriminator
    // -------------------------------------------------------------------------

    #[Test]
    public function getDiscriminatorReturnsStructure(): void
    {
        self::assertSame('structure', (new StructureAggregate())->getDiscriminator());
    }

    // -------------------------------------------------------------------------
    // toJsonSchema — no properties (generic / backward-compat)
    // -------------------------------------------------------------------------

    #[Test]
    public function toJsonSchemaWithNoPropertiesReturnsGenericSchema(): void
    {
        $schema = (new StructureAggregate())->toJsonSchema();

        self::assertSame('object', $schema->jsonSerialize()['type']);
        self::assertSame('structure', $schema->jsonSerialize()['properties']['type']['const']);
        self::assertSame('object', $schema->jsonSerialize()['properties']['value']['type']);
        self::assertArrayHasKey('$comment', $schema->jsonSerialize()['properties']['value']);
        self::assertSame(['type', 'value'], $schema->jsonSerialize()['required']);
        self::assertFalse($schema->jsonSerialize()['additionalProperties']);
    }

    #[Test]
    public function toJsonSchemaWithNoPropertiesHasNoValueProperties(): void
    {
        $schema = (new StructureAggregate())->toJsonSchema();

        self::assertArrayNotHasKey('properties', $schema->jsonSerialize()['properties']['value']);
        self::assertArrayNotHasKey('required', $schema->jsonSerialize()['properties']['value']);
    }

    // -------------------------------------------------------------------------
    // toJsonSchema — with PropertyDefinitions
    // -------------------------------------------------------------------------

    #[Test]
    public function toJsonSchemaWithPropertiesEmbedsTypedValueSchema(): void
    {
        $structure = StructureAggregate::fromDefinition(
            new PropertyDefinition('title', 'string', 'Short title'),
            new PropertyDefinition('count', 'integer'),
        );
        $schema = $structure->toJsonSchema();

        self::assertSame('structure', $schema->jsonSerialize()['properties']['type']['const']);
        $value = $schema->jsonSerialize()['properties']['value'];
        self::assertSame('object', $value['type']);
        self::assertSame('string', $value['properties']['title']['type']);
        self::assertSame('Short title', $value['properties']['title']['$comment']);
        self::assertSame('integer', $value['properties']['count']['type']);
        self::assertArrayNotHasKey('$comment', $value['properties']['count']);
        self::assertSame(['title', 'count'], $value['required']);
        self::assertFalse($value['additionalProperties']);
    }

    #[Test]
    public function toJsonSchemaWithPropertiesHasAdditionalPropertiesFalseAtTopLevel(): void
    {
        $schema = StructureAggregate::fromDefinition(
            new PropertyDefinition('x', 'string'),
        )->toJsonSchema();

        self::assertFalse($schema->jsonSerialize()['additionalProperties']);
    }

    // -------------------------------------------------------------------------
    // parse
    // -------------------------------------------------------------------------

    #[Test]
    public function parsePopulatesValue(): void
    {
        $data = ['city' => 'Berlin', 'country' => 'Germany', 'population' => 3645000];
        $structure = (new StructureAggregate())->parse(['type' => 'structure', 'value' => $data]);

        self::assertInstanceOf(StructureAggregate::class, $structure);
        self::assertSame($data, $structure->value);
    }

    #[Test]
    public function parsePreservesProperties(): void
    {
        $def = new PropertyDefinition('name', 'string');
        $original = StructureAggregate::fromDefinition($def);
        $parsed = $original->parse(['type' => 'structure', 'value' => ['name' => 'foo']]);

        self::assertSame(['name' => 'foo'], $parsed->value);
        // schema still reflects defined property
        self::assertArrayHasKey('name', $parsed->toJsonSchema()->jsonSerialize()['properties']['value']['properties']);
    }

    #[Test]
    public function parseDoesNotMutateDescriptor(): void
    {
        $descriptor = StructureAggregate::fromDefinition(new PropertyDefinition('x', 'string'));
        $descriptor->parse(['type' => 'structure', 'value' => ['x' => 'y']]);

        self::assertSame([], $descriptor->value);
    }

    // -------------------------------------------------------------------------
    // fromDefinition
    // -------------------------------------------------------------------------

    #[Test]
    public function fromDefinitionWithZeroPropertiesProducesGenericSchema(): void
    {
        $schema = StructureAggregate::fromDefinition()->toJsonSchema();

        self::assertArrayNotHasKey('properties', $schema->jsonSerialize()['properties']['value']);
    }

    #[Test]
    public function fromDefinitionWithMixedPropertiesBuildsCorrectSchema(): void
    {
        $schema = StructureAggregate::fromDefinition(
            new PropertyDefinition('label', 'string', 'Human-readable label'),
            new PropertyDefinition('active', 'boolean'),
            new PropertyDefinition('score', 'number'),
        )->toJsonSchema();

        $props = $schema->jsonSerialize()['properties']['value']['properties'];
        self::assertSame('string', $props['label']['type']);
        self::assertSame('Human-readable label', $props['label']['$comment']);
        self::assertSame('boolean', $props['active']['type']);
        self::assertSame('number', $props['score']['type']);
    }

    // -------------------------------------------------------------------------
    // fromTcaSchema
    // -------------------------------------------------------------------------

    #[Test]
    public function fromTcaSchemaDerivesStringTypeForInputField(): void
    {
        $field = $this->createMock(FieldTypeInterface::class);
        $field->method('getType')->willReturn('input');
        $field->method('getLabel')->willReturn('My Label');

        $schema = $this->createMock(TcaSchema::class);
        $schema->method('getField')->with('my_field')->willReturn($field);

        $result = StructureAggregate::fromTcaSchema($schema, 'my_field');
        $props = $result->toJsonSchema()->jsonSerialize()['properties']['value']['properties'];

        self::assertSame('string', $props['my_field']['type']);
        self::assertSame('My Label', $props['my_field']['$comment']);
    }

    #[Test]
    public function fromTcaSchemaDerivesIntegerTypeForNumberIntegerField(): void
    {
        $field = new NumberFieldType('count', ['format' => 'integer']);

        $schema = $this->createMock(TcaSchema::class);
        $schema->method('getField')->with('count')->willReturn($field);

        $result = StructureAggregate::fromTcaSchema($schema, 'count');
        $props = $result->toJsonSchema()->jsonSerialize()['properties']['value']['properties'];

        self::assertSame('integer', $props['count']['type']);
        self::assertArrayNotHasKey('$comment', $props['count']);
    }

    #[Test]
    public function fromTcaSchemaDerivesNumberTypeForNumberDecimalField(): void
    {
        $field = new NumberFieldType('price', ['format' => 'decimal']);

        $schema = $this->createMock(TcaSchema::class);
        $schema->method('getField')->with('price')->willReturn($field);

        $result = StructureAggregate::fromTcaSchema($schema, 'price');
        $props = $result->toJsonSchema()->jsonSerialize()['properties']['value']['properties'];

        self::assertSame('number', $props['price']['type']);
    }

    #[Test]
    public function fromTcaSchemaDerivatesBooleanTypeForCheckField(): void
    {
        $field = $this->createMock(FieldTypeInterface::class);
        $field->method('getType')->willReturn('check');
        $field->method('getLabel')->willReturn('Is active');

        $schema = $this->createMock(TcaSchema::class);
        $schema->method('getField')->with('active')->willReturn($field);

        $result = StructureAggregate::fromTcaSchema($schema, 'active');
        $props = $result->toJsonSchema()->jsonSerialize()['properties']['value']['properties'];

        self::assertSame('boolean', $props['active']['type']);
    }

    #[Test]
    public function fromTcaSchemaPassesThroughPropertyDefinitionInstances(): void
    {
        $schema = $this->createMock(TcaSchema::class);
        $schema->expects($this->never())->method('getField');

        $def = new PropertyDefinition('custom', 'integer', 'My comment');
        $result = StructureAggregate::fromTcaSchema($schema, $def);
        $props = $result->toJsonSchema()->jsonSerialize()['properties']['value']['properties'];

        self::assertSame('integer', $props['custom']['type']);
        self::assertSame('My comment', $props['custom']['$comment']);
    }
}
