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
use TYPO3\CMS\Assist\AI\Assistant\Type\BinaryType;
use TYPO3\CMS\Assist\AI\Assistant\Type\IntersectionAggregate;
use TYPO3\CMS\Assist\AI\Assistant\Type\ListAggregate;
use TYPO3\CMS\Assist\AI\Assistant\Type\PropertyDefinition;
use TYPO3\CMS\Assist\AI\Assistant\Type\StructureAggregate;
use TYPO3\CMS\Assist\AI\Assistant\Type\TextType;
use TYPO3\CMS\Assist\AI\Assistant\Type\UnionAggregate;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ListAggregateTest extends UnitTestCase
{
    // -------------------------------------------------------------------------
    // of() factory
    // -------------------------------------------------------------------------

    #[Test]
    public function ofReturnsItemAggregateTypeInstance(): void
    {
        self::assertInstanceOf(ListAggregate::class, ListAggregate::of(TextType::class));
    }

    #[Test]
    public function ofWithClassStringCreatesDescriptorWithEmptyItems(): void
    {
        $descriptor = ListAggregate::of(TextType::class);

        self::assertSame([], $descriptor->items);
    }

    // -------------------------------------------------------------------------
    // toJsonSchema
    // -------------------------------------------------------------------------

    #[Test]
    public function toJsonSchemaHasListTypeDiscriminator(): void
    {
        $schema = (new ListAggregate(TextType::class))->toJsonSchema();

        self::assertSame('list', $schema->jsonSerialize()['properties']['type']['const']);
    }

    #[Test]
    public function toJsonSchemaWithClassStringUsesAtomicSchema(): void
    {
        $schema = (new ListAggregate(TextType::class))->toJsonSchema();

        self::assertSame('array', $schema->jsonSerialize()['properties']['items']['type']);
        self::assertSame(TextType::toJsonSchema()->jsonSerialize(), $schema->jsonSerialize()['properties']['items']['items']);
    }

    #[Test]
    public function toJsonSchemaWithUnionTypeUsesOneOfSchema(): void
    {
        $union = UnionAggregate::of(TextType::class, BinaryType::class);
        $schema = (new ListAggregate($union))->toJsonSchema();

        self::assertArrayHasKey('oneOf', $schema->jsonSerialize()['properties']['items']['items']);
        self::assertCount(2, $schema->jsonSerialize()['properties']['items']['items']['oneOf']);
    }

    #[Test]
    public function toJsonSchemaDoesNotIncludeMinItems(): void
    {
        $schema = (new ListAggregate(TextType::class))->toJsonSchema();

        self::assertArrayNotHasKey('minItems', $schema->jsonSerialize()['properties']['items']);
    }

    // -------------------------------------------------------------------------
    // parse
    // -------------------------------------------------------------------------

    #[Test]
    public function parseWithClassStringReturnsItemAggregateTypeInstance(): void
    {
        $result = (new ListAggregate(TextType::class))->parse([
            'type' => 'list',
            'items' => [['type' => 'text', 'value' => 'hello']],
        ]);

        self::assertInstanceOf(ListAggregate::class, $result);
    }

    #[Test]
    public function parseWithClassStringPopulatesTypedItems(): void
    {
        $result = (new ListAggregate(TextType::class))->parse([
            'type' => 'list',
            'items' => [
                ['type' => 'text', 'value' => 'first'],
                ['type' => 'text', 'value' => 'second'],
            ],
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
        $result = (new ListAggregate(UnionAggregate::of(TextType::class, BinaryType::class)))->parse([
            'type' => 'list',
            'items' => [
                ['type' => 'text', 'value' => 'hello'],
                ['type' => 'binary', 'data' => 'abc='],
                ['type' => 'text', 'value' => 'world'],
            ],
        ]);

        self::assertCount(3, $result->items);
        self::assertInstanceOf(TextType::class, $result->items[0]);
        self::assertInstanceOf(BinaryType::class, $result->items[1]);
        self::assertInstanceOf(TextType::class, $result->items[2]);
        self::assertSame('hello', $result->items[0]->value);
        self::assertNull($result->items[1]->mimeType);
    }

    #[Test]
    public function parseWithEmptyItemsReturnsEmptyList(): void
    {
        $result = (new ListAggregate(TextType::class))->parse(['type' => 'list', 'items' => []]);

        self::assertInstanceOf(ListAggregate::class, $result);
        self::assertSame([], $result->items);
    }

    #[Test]
    public function parseDoesNotMutateDescriptor(): void
    {
        $descriptor = new ListAggregate(TextType::class);
        $descriptor->parse([
            'type' => 'list',
            'items' => [['type' => 'text', 'value' => 'x']],
        ]);

        self::assertEmpty($descriptor->items);
    }

    // -------------------------------------------------------------------------
    // StructureAggregate as item type (AggregateInterface instance)
    // -------------------------------------------------------------------------

    #[Test]
    public function ofWithStructureAggregateInstanceCreatesDescriptor(): void
    {
        $itemType = StructureAggregate::fromDefinition(new PropertyDefinition('title', 'string'));
        self::assertInstanceOf(ListAggregate::class, ListAggregate::of($itemType));
    }

    #[Test]
    public function toJsonSchemaWithStructureAggregateEmbeddsTypedSchema(): void
    {
        $itemType = StructureAggregate::fromDefinition(
            new PropertyDefinition('title', 'string', 'Short title'),
            new PropertyDefinition('score', 'integer'),
        );
        $schema = ListAggregate::of($itemType)->toJsonSchema();

        $itemsSchema = $schema->jsonSerialize()['properties']['items']['items'];
        self::assertSame('structure', $itemsSchema['properties']['type']['const']);
        self::assertSame('string', $itemsSchema['properties']['value']['properties']['title']['type']);
        self::assertSame('integer', $itemsSchema['properties']['value']['properties']['score']['type']);
    }

    #[Test]
    public function parseWithStructureAggregatePopulatesItems(): void
    {
        $itemType = StructureAggregate::fromDefinition(
            new PropertyDefinition('title', 'string'),
            new PropertyDefinition('description', 'string'),
        );
        $result = ListAggregate::of($itemType)->parse([
            'type' => 'list',
            'items' => [
                ['type' => 'structure', 'value' => ['title' => 'Foo', 'description' => 'Bar']],
                ['type' => 'structure', 'value' => ['title' => 'Baz', 'description' => 'Qux']],
            ],
        ]);

        self::assertCount(2, $result->items);
        self::assertInstanceOf(StructureAggregate::class, $result->items[0]);
        self::assertSame(['title' => 'Foo', 'description' => 'Bar'], $result->items[0]->value);
        self::assertSame(['title' => 'Baz', 'description' => 'Qux'], $result->items[1]->value);
    }

    // -------------------------------------------------------------------------
    // IntersectionAggregate as item type
    // -------------------------------------------------------------------------

    #[Test]
    public function ofWithIntersectionAggregateCreatesDescriptor(): void
    {
        $itemType = IntersectionAggregate::of(TextType::class, BinaryType::class);
        self::assertInstanceOf(ListAggregate::class, ListAggregate::of($itemType));
    }

    #[Test]
    public function toJsonSchemaWithIntersectionAggregateEmbeddsAllOfSchema(): void
    {
        $itemType = IntersectionAggregate::of(TextType::class, BinaryType::class);
        $schema = ListAggregate::of($itemType)->toJsonSchema();

        $itemsSchema = $schema->jsonSerialize()['properties']['items']['items'];
        self::assertArrayHasKey('allOf', $itemsSchema);
        self::assertCount(2, $itemsSchema['allOf']);
    }

    #[Test]
    public function parseWithIntersectionAggregatePopulatesItemParts(): void
    {
        $itemType = IntersectionAggregate::of(TextType::class);
        $result = ListAggregate::of($itemType)->parse([
            'type' => 'list',
            'items' => [
                ['type' => 'text', 'value' => 'hello'],
                ['type' => 'text', 'value' => 'world'],
            ],
        ]);

        self::assertCount(2, $result->items);
        self::assertInstanceOf(IntersectionAggregate::class, $result->items[0]);
        self::assertCount(1, $result->items[0]->parts);
        self::assertInstanceOf(TextType::class, $result->items[0]->parts[0]);
        self::assertSame('hello', $result->items[0]->parts[0]->value);
    }
}
