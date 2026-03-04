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

namespace TYPO3\CMS\Assist\Tests\Unit\AI\Format;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Assist\AI\Format\Binary;
use TYPO3\CMS\Assist\AI\Format\ItemList;
use TYPO3\CMS\Assist\AI\Format\Message;
use TYPO3\CMS\Assist\AI\Format\UnionType;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ItemListTest extends UnitTestCase
{
    // -------------------------------------------------------------------------
    // toJsonSchema
    // -------------------------------------------------------------------------

    #[Test]
    public function toJsonSchemaHasListTypeDiscriminator(): void
    {
        $schema = (new ItemList(Message::class))->toJsonSchema();

        self::assertSame('list', $schema['properties']['type']['const']);
    }

    #[Test]
    public function toJsonSchemaWithClassStringUsesAtomicSchema(): void
    {
        $schema = (new ItemList(Message::class))->toJsonSchema();

        self::assertSame('array', $schema['properties']['items']['type']);
        self::assertSame(Message::toJsonSchema(), $schema['properties']['items']['items']);
    }

    #[Test]
    public function toJsonSchemaWithUnionTypeUsesOneOfSchema(): void
    {
        $union = UnionType::of(Message::class, Binary::class);
        $schema = (new ItemList($union))->toJsonSchema();

        self::assertArrayHasKey('oneOf', $schema['properties']['items']['items']);
        self::assertCount(2, $schema['properties']['items']['items']['oneOf']);
    }

    #[Test]
    public function toJsonSchemaDoesNotIncludeMinItems(): void
    {
        $schema = (new ItemList(Message::class))->toJsonSchema();

        self::assertArrayNotHasKey('minItems', $schema['properties']['items']);
    }

    // -------------------------------------------------------------------------
    // parse
    // -------------------------------------------------------------------------

    #[Test]
    public function parseWithClassStringReturnsItemListInstance(): void
    {
        $result = (new ItemList(Message::class))->parse([
            'type' => 'list',
            'items' => [['type' => 'message', 'value' => 'hello']],
        ]);

        self::assertInstanceOf(ItemList::class, $result);
    }

    #[Test]
    public function parseWithClassStringPopulatesTypedItems(): void
    {
        $result = (new ItemList(Message::class))->parse([
            'type' => 'list',
            'items' => [
                ['type' => 'message', 'value' => 'first'],
                ['type' => 'message', 'value' => 'second'],
            ],
        ]);

        self::assertCount(2, $result->items);
        self::assertInstanceOf(Message::class, $result->items[0]);
        self::assertSame('first', $result->items[0]->value);
        self::assertInstanceOf(Message::class, $result->items[1]);
        self::assertSame('second', $result->items[1]->value);
    }

    #[Test]
    public function parseWithUnionTypeDispatchesToCorrectTypes(): void
    {
        $result = (new ItemList(UnionType::of(Message::class, Binary::class)))->parse([
            'type' => 'list',
            'items' => [
                ['type' => 'message', 'value' => 'hello'],
                ['type' => 'binary', 'data' => 'abc='],
                ['type' => 'message', 'value' => 'world'],
            ],
        ]);

        self::assertCount(3, $result->items);
        self::assertInstanceOf(Message::class, $result->items[0]);
        self::assertInstanceOf(Binary::class, $result->items[1]);
        self::assertInstanceOf(Message::class, $result->items[2]);
        self::assertSame('hello', $result->items[0]->value);
        self::assertNull($result->items[1]->mimeType);
    }

    #[Test]
    public function parseWithEmptyItemsReturnsEmptyList(): void
    {
        $result = (new ItemList(Message::class))->parse(['type' => 'list', 'items' => []]);

        self::assertInstanceOf(ItemList::class, $result);
        self::assertSame([], $result->items);
    }

    #[Test]
    public function parseDoesNotMutateDescriptor(): void
    {
        $descriptor = new ItemList(Message::class);
        $descriptor->parse([
            'type' => 'list',
            'items' => [['type' => 'message', 'value' => 'x']],
        ]);

        self::assertEmpty($descriptor->items);
    }
}
