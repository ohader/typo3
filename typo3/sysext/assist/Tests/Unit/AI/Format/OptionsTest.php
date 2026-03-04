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
use TYPO3\CMS\Assist\AI\Format\Message;
use TYPO3\CMS\Assist\AI\Format\Options;
use TYPO3\CMS\Assist\AI\Format\UnionType;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class OptionsTest extends UnitTestCase
{
    // -------------------------------------------------------------------------
    // toJsonSchema
    // -------------------------------------------------------------------------

    #[Test]
    public function toJsonSchemaHasOptionsTypeDiscriminator(): void
    {
        $schema = (new Options(Message::class))->toJsonSchema();

        self::assertSame('options', $schema['properties']['type']['const']);
    }

    #[Test]
    public function toJsonSchemaEnforcesMinItemsOfTwo(): void
    {
        $schema = (new Options(Message::class))->toJsonSchema();

        self::assertSame(2, $schema['properties']['items']['minItems']);
    }

    #[Test]
    public function toJsonSchemaWithClassStringUsesAtomicSchema(): void
    {
        $schema = (new Options(Message::class))->toJsonSchema();

        self::assertSame('array', $schema['properties']['items']['type']);
        self::assertSame(Message::toJsonSchema(), $schema['properties']['items']['items']);
    }

    #[Test]
    public function toJsonSchemaWithUnionTypeUsesOneOfSchema(): void
    {
        $union = UnionType::of(Message::class, Binary::class);
        $schema = (new Options($union))->toJsonSchema();

        self::assertArrayHasKey('oneOf', $schema['properties']['items']['items']);
        self::assertCount(2, $schema['properties']['items']['items']['oneOf']);
    }

    #[Test]
    public function toJsonSchemaHasRequiredTypeAndItems(): void
    {
        $schema = (new Options(Message::class))->toJsonSchema();

        self::assertSame(['type', 'items'], $schema['required']);
    }

    // -------------------------------------------------------------------------
    // parse
    // -------------------------------------------------------------------------

    #[Test]
    public function parseWithClassStringReturnsOptionsInstance(): void
    {
        $result = (new Options(Message::class))->parse([
            'type' => 'options',
            'items' => [
                ['type' => 'message', 'value' => 'Yes'],
                ['type' => 'message', 'value' => 'No'],
            ],
        ]);

        self::assertInstanceOf(Options::class, $result);
    }

    #[Test]
    public function parseWithClassStringPopulatesTypedItems(): void
    {
        $result = (new Options(Message::class))->parse([
            'type' => 'options',
            'items' => [
                ['type' => 'message', 'value' => 'Yes'],
                ['type' => 'message', 'value' => 'No'],
                ['type' => 'message', 'value' => 'Maybe'],
            ],
        ]);

        self::assertCount(3, $result->items);
        self::assertInstanceOf(Message::class, $result->items[0]);
        self::assertSame('Yes', $result->items[0]->value);
        self::assertSame('No', $result->items[1]->value);
        self::assertSame('Maybe', $result->items[2]->value);
    }

    #[Test]
    public function parseWithUnionTypeDispatchesToCorrectTypes(): void
    {
        $result = (new Options(UnionType::of(Message::class, Binary::class)))->parse([
            'type' => 'options',
            'items' => [
                ['type' => 'message', 'value' => 'Text option'],
                ['type' => 'binary', 'data' => 'abc=', 'mimeType' => 'image/png'],
            ],
        ]);

        self::assertCount(2, $result->items);
        self::assertInstanceOf(Message::class, $result->items[0]);
        self::assertInstanceOf(Binary::class, $result->items[1]);
        self::assertSame('image/png', $result->items[1]->mimeType);
    }

    #[Test]
    public function parseDoesNotMutateDescriptor(): void
    {
        $descriptor = new Options(Message::class);
        $descriptor->parse([
            'type' => 'options',
            'items' => [
                ['type' => 'message', 'value' => 'A'],
                ['type' => 'message', 'value' => 'B'],
            ],
        ]);

        self::assertEmpty($descriptor->items);
    }
}
