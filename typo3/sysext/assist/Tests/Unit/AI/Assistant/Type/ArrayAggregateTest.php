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
use TYPO3\CMS\Assist\AI\Assistant\Type\ArrayAggregate;
use TYPO3\CMS\Assist\AI\Assistant\Type\BinaryType;
use TYPO3\CMS\Assist\AI\Assistant\Type\PropertyDefinition;
use TYPO3\CMS\Assist\AI\Assistant\Type\StructureAggregate;
use TYPO3\CMS\Assist\AI\Assistant\Type\TextType;
use TYPO3\CMS\Assist\AI\Assistant\Type\UnionAggregate;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ArrayAggregateTest extends UnitTestCase
{
    // -------------------------------------------------------------------------
    // of() factory
    // -------------------------------------------------------------------------

    #[Test]
    public function ofReturnsArrayAggregateInstance(): void
    {
        self::assertInstanceOf(ArrayAggregate::class, ArrayAggregate::of(TextType::class));
    }

    #[Test]
    public function ofWithClassStringCreatesDescriptorWithEmptyItems(): void
    {
        $descriptor = ArrayAggregate::of(TextType::class);

        self::assertSame([], $descriptor->items);
    }

    // -------------------------------------------------------------------------
    // toJsonSchema
    // -------------------------------------------------------------------------

    #[Test]
    public function toJsonSchemaHasArrayTypeAtTopLevel(): void
    {
        $schema = (new ArrayAggregate(TextType::class))->toJsonSchema();

        self::assertSame('array', $schema->jsonSerialize()['type']);
        self::assertArrayNotHasKey('properties', $schema->jsonSerialize());
        self::assertArrayNotHasKey('minItems', $schema->jsonSerialize());
    }

    #[Test]
    public function toJsonSchemaWithClassStringUsesAtomicSchemaUnderItems(): void
    {
        $schema = (new ArrayAggregate(TextType::class))->toJsonSchema();

        self::assertSame(TextType::toJsonSchema()->jsonSerialize(), $schema->jsonSerialize()['items']);
    }

    #[Test]
    public function toJsonSchemaWithUnionTypeUsesOneOfSchemaUnderItems(): void
    {
        $union = UnionAggregate::of(TextType::class, BinaryType::class);
        $schema = (new ArrayAggregate($union))->toJsonSchema();

        self::assertArrayHasKey('oneOf', $schema->jsonSerialize()['items']);
        self::assertCount(2, $schema->jsonSerialize()['items']['oneOf']);
    }

    #[Test]
    public function toJsonSchemaWithStructureAggregateEmbeddsTypedSchemaUnderItems(): void
    {
        $itemType = StructureAggregate::fromDefinition(
            new PropertyDefinition('title', 'string', 'Short title'),
            new PropertyDefinition('score', 'integer'),
        );
        $schema = ArrayAggregate::of($itemType)->toJsonSchema();

        $itemsSchema = $schema->jsonSerialize()['items'];
        self::assertSame('structure', $itemsSchema['properties']['type']['const']);
        self::assertSame('string', $itemsSchema['properties']['value']['properties']['title']['type']);
        self::assertSame('integer', $itemsSchema['properties']['value']['properties']['score']['type']);
    }

    // -------------------------------------------------------------------------
    // getDiscriminator
    // -------------------------------------------------------------------------

    #[Test]
    public function getDiscriminatorReturnsArray(): void
    {
        self::assertSame('array', (new ArrayAggregate(TextType::class))->getDiscriminator());
    }

    // -------------------------------------------------------------------------
    // parse
    // -------------------------------------------------------------------------

    #[Test]
    public function parseWithClassStringReturnsArrayAggregateInstance(): void
    {
        $result = (new ArrayAggregate(TextType::class))->parse([
            ['type' => 'text', 'value' => 'hello'],
        ]);

        self::assertInstanceOf(ArrayAggregate::class, $result);
    }

    #[Test]
    public function parseWithClassStringPopulatesTypedItems(): void
    {
        $result = (new ArrayAggregate(TextType::class))->parse([
            ['type' => 'text', 'value' => 'first'],
            ['type' => 'text', 'value' => 'second'],
        ]);

        self::assertCount(2, $result->items);
        self::assertInstanceOf(TextType::class, $result->items[0]);
        self::assertSame('first', $result->items[0]->value);
        self::assertInstanceOf(TextType::class, $result->items[1]);
        self::assertSame('second', $result->items[1]->value);
    }

    #[Test]
    public function parseWithUnionTypeDispatchesToCorrectTypes(): void
    {
        $result = (new ArrayAggregate(UnionAggregate::of(TextType::class, BinaryType::class)))->parse([
            ['type' => 'text', 'value' => 'hello'],
            ['type' => 'binary', 'data' => 'abc='],
            ['type' => 'text', 'value' => 'world'],
        ]);

        self::assertCount(3, $result->items);
        self::assertInstanceOf(TextType::class, $result->items[0]);
        self::assertInstanceOf(BinaryType::class, $result->items[1]);
        self::assertInstanceOf(TextType::class, $result->items[2]);
        self::assertSame('hello', $result->items[0]->value);
        self::assertNull($result->items[1]->mimeType);
    }

    #[Test]
    public function parseWithStructureAggregatePopulatesStructureItems(): void
    {
        $itemType = StructureAggregate::fromDefinition(
            new PropertyDefinition('title', 'string'),
            new PropertyDefinition('description', 'string'),
        );
        $result = ArrayAggregate::of($itemType)->parse([
            ['type' => 'structure', 'value' => ['title' => 'Foo', 'description' => 'Bar']],
            ['type' => 'structure', 'value' => ['title' => 'Baz', 'description' => 'Qux']],
        ]);

        self::assertCount(2, $result->items);
        self::assertInstanceOf(StructureAggregate::class, $result->items[0]);
        self::assertSame(['title' => 'Foo', 'description' => 'Bar'], $result->items[0]->value);
        self::assertSame(['title' => 'Baz', 'description' => 'Qux'], $result->items[1]->value);
    }

    #[Test]
    public function parseWithEmptyArrayReturnsEmptyItems(): void
    {
        $result = (new ArrayAggregate(TextType::class))->parse([]);

        self::assertInstanceOf(ArrayAggregate::class, $result);
        self::assertSame([], $result->items);
    }

    #[Test]
    public function parseDoesNotMutateDescriptor(): void
    {
        $descriptor = new ArrayAggregate(TextType::class);
        $descriptor->parse([
            ['type' => 'text', 'value' => 'x'],
        ]);

        self::assertEmpty($descriptor->items);
    }
}
