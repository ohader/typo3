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
use TYPO3\CMS\Assist\AI\Assistant\Type\OptionsAggregate;
use TYPO3\CMS\Assist\AI\Assistant\Type\TextType;
use TYPO3\CMS\Assist\AI\Assistant\Type\UnionAggregate;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class OptionsAggregateTest extends UnitTestCase
{
    // -------------------------------------------------------------------------
    // of() factory
    // -------------------------------------------------------------------------

    #[Test]
    public function ofReturnsOptionsAggregateInstance(): void
    {
        self::assertInstanceOf(OptionsAggregate::class, OptionsAggregate::of(TextType::class));
    }

    #[Test]
    public function ofWithClassStringCreatesDescriptorWithEmptyItems(): void
    {
        $descriptor = OptionsAggregate::of(TextType::class);

        self::assertSame([], $descriptor->items);
    }

    // -------------------------------------------------------------------------
    // toJsonSchema
    // -------------------------------------------------------------------------

    #[Test]
    public function toJsonSchemaHasOptionsTypeDiscriminator(): void
    {
        $schema = (new OptionsAggregate(TextType::class))->toJsonSchema();

        self::assertSame('options', $schema->jsonSerialize()['properties']['type']['const']);
    }

    #[Test]
    public function toJsonSchemaEnforcesMinItemsOfTwo(): void
    {
        $schema = (new OptionsAggregate(TextType::class))->toJsonSchema();

        self::assertSame(2, $schema->jsonSerialize()['properties']['items']['minItems']);
    }

    #[Test]
    public function toJsonSchemaWithClassStringUsesAtomicSchema(): void
    {
        $schema = (new OptionsAggregate(TextType::class))->toJsonSchema();

        self::assertSame('array', $schema->jsonSerialize()['properties']['items']['type']);
        self::assertSame(TextType::toJsonSchema()->jsonSerialize(), $schema->jsonSerialize()['properties']['items']['items']);
    }

    #[Test]
    public function toJsonSchemaWithUnionAggregateUsesOneOfSchema(): void
    {
        $union = UnionAggregate::of(TextType::class, BinaryType::class);
        $schema = (new OptionsAggregate($union))->toJsonSchema();

        self::assertArrayHasKey('oneOf', $schema->jsonSerialize()['properties']['items']['items']);
        self::assertCount(2, $schema->jsonSerialize()['properties']['items']['items']['oneOf']);
    }

    #[Test]
    public function toJsonSchemaHasRequiredTypeAndItems(): void
    {
        $schema = (new OptionsAggregate(TextType::class))->toJsonSchema();

        self::assertSame(['type', 'items'], $schema->jsonSerialize()['required']);
    }

    // -------------------------------------------------------------------------
    // parse
    // -------------------------------------------------------------------------

    #[Test]
    public function parseWithClassStringReturnsOptionsAggregateInstance(): void
    {
        $result = (new OptionsAggregate(TextType::class))->parse([
            'type' => 'options',
            'items' => [
                ['type' => 'text', 'value' => 'Yes'],
                ['type' => 'text', 'value' => 'No'],
            ],
        ]);

        self::assertInstanceOf(OptionsAggregate::class, $result);
    }

    #[Test]
    public function parseWithClassStringPopulatesTypedItems(): void
    {
        $result = (new OptionsAggregate(TextType::class))->parse([
            'type' => 'options',
            'items' => [
                ['type' => 'text', 'value' => 'Yes'],
                ['type' => 'text', 'value' => 'No'],
                ['type' => 'text', 'value' => 'Maybe'],
            ],
        ]);

        self::assertCount(3, $result->items);
        self::assertInstanceOf(TextType::class, $result->items[0]);
        self::assertSame('Yes', $result->items[0]->value);
        self::assertSame('No', $result->items[1]->value);
        self::assertSame('Maybe', $result->items[2]->value);
    }

    #[Test]
    public function parseWithUnionAggregateDispatchesToCorrectTypes(): void
    {
        $result = (new OptionsAggregate(UnionAggregate::of(TextType::class, BinaryType::class)))->parse([
            'type' => 'options',
            'items' => [
                ['type' => 'text', 'value' => 'Text option'],
                ['type' => 'binary', 'data' => 'abc=', 'mimeType' => 'image/png'],
            ],
        ]);

        self::assertCount(2, $result->items);
        self::assertInstanceOf(TextType::class, $result->items[0]);
        self::assertInstanceOf(BinaryType::class, $result->items[1]);
        self::assertSame('image/png', $result->items[1]->mimeType);
    }

    #[Test]
    public function parseDoesNotMutateDescriptor(): void
    {
        $descriptor = new OptionsAggregate(TextType::class);
        $descriptor->parse([
            'type' => 'options',
            'items' => [
                ['type' => 'text', 'value' => 'A'],
                ['type' => 'text', 'value' => 'B'],
            ],
        ]);

        self::assertEmpty($descriptor->items);
    }

    // -------------------------------------------------------------------------
    // IntersectionAggregate as item type
    // -------------------------------------------------------------------------

    #[Test]
    public function ofWithIntersectionAggregateCreatesDescriptor(): void
    {
        $itemType = IntersectionAggregate::of(TextType::class, BinaryType::class);
        self::assertInstanceOf(OptionsAggregate::class, OptionsAggregate::of($itemType));
    }

    #[Test]
    public function toJsonSchemaWithIntersectionAggregateEmbeddsAllOfSchema(): void
    {
        $itemType = IntersectionAggregate::of(TextType::class, BinaryType::class);
        $schema = (new OptionsAggregate($itemType))->toJsonSchema();

        self::assertArrayHasKey('allOf', $schema->jsonSerialize()['properties']['items']['items']);
        self::assertCount(2, $schema->jsonSerialize()['properties']['items']['items']['allOf']);
    }

    #[Test]
    public function parseWithIntersectionAggregatePopulatesItemParts(): void
    {
        $itemType = IntersectionAggregate::of(TextType::class);
        $result = (new OptionsAggregate($itemType))->parse([
            'type' => 'options',
            'items' => [
                ['type' => 'text', 'value' => 'Yes'],
                ['type' => 'text', 'value' => 'No'],
            ],
        ]);

        self::assertCount(2, $result->items);
        self::assertInstanceOf(IntersectionAggregate::class, $result->items[0]);
        self::assertCount(1, $result->items[0]->parts);
        self::assertInstanceOf(TextType::class, $result->items[0]->parts[0]);
        self::assertSame('Yes', $result->items[0]->parts[0]->value);
    }
}
