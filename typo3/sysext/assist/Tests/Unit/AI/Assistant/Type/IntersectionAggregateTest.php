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
use TYPO3\CMS\Assist\AI\Assistant\Type\StructureAggregate;
use TYPO3\CMS\Assist\AI\Assistant\Type\TextType;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class IntersectionAggregateTest extends UnitTestCase
{
    #[Test]
    public function toJsonSchemaWithSingleTypeReturnsAllOfWithOneEntry(): void
    {
        $schema = IntersectionAggregate::of(TextType::class)->toJsonSchema();

        self::assertArrayHasKey('allOf', $schema->jsonSerialize());
        self::assertCount(1, $schema->jsonSerialize()['allOf']);
        self::assertSame(TextType::toJsonSchema()->jsonSerialize(), $schema->jsonSerialize()['allOf'][0]);
    }

    #[Test]
    public function toJsonSchemaWithMultipleTypesIncludesAllSchemas(): void
    {
        $structure = new StructureAggregate();
        $schema = IntersectionAggregate::of(TextType::class, BinaryType::class, $structure)->toJsonSchema();

        self::assertArrayHasKey('allOf', $schema->jsonSerialize());
        self::assertCount(3, $schema->jsonSerialize()['allOf']);
        self::assertSame(TextType::toJsonSchema()->jsonSerialize(), $schema->jsonSerialize()['allOf'][0]);
        self::assertSame(BinaryType::toJsonSchema()->jsonSerialize(), $schema->jsonSerialize()['allOf'][1]);
        self::assertSame($structure->toJsonSchema()->jsonSerialize(), $schema->jsonSerialize()['allOf'][2]);
    }

    #[Test]
    public function toJsonSchemaContainsNoExtraKeys(): void
    {
        $schema = IntersectionAggregate::of(TextType::class)->toJsonSchema();

        self::assertSame(['allOf'], array_keys($schema->jsonSerialize()));
    }

    #[Test]
    public function toJsonSchemaWithAggregateInstanceIncludesItsSchema(): void
    {
        $structure = new StructureAggregate();
        $schema = IntersectionAggregate::of(TextType::class, $structure)->toJsonSchema();

        self::assertCount(2, $schema->jsonSerialize()['allOf']);
        self::assertSame(TextType::toJsonSchema()->jsonSerialize(), $schema->jsonSerialize()['allOf'][0]);
        self::assertSame($structure->toJsonSchema()->jsonSerialize(), $schema->jsonSerialize()['allOf'][1]);
    }

    #[Test]
    public function parsePopulatesPartsForEachType(): void
    {
        $result = IntersectionAggregate::of(TextType::class, TextType::class)
            ->parse(['type' => 'text', 'value' => 'hello']);

        self::assertCount(2, $result->parts);
    }

    #[Test]
    public function parseDispatchesStaticTypeCorrectly(): void
    {
        $result = IntersectionAggregate::of(TextType::class)
            ->parse(['type' => 'text', 'value' => 'hello']);

        self::assertInstanceOf(TextType::class, $result->parts[0]);
        self::assertSame('hello', $result->parts[0]->value);
    }

    #[Test]
    public function parseDispatchesAggregateCorrectly(): void
    {
        $structure = new StructureAggregate();
        $result = IntersectionAggregate::of($structure)
            ->parse(['value' => ['key' => 'val']]);

        self::assertInstanceOf(StructureAggregate::class, $result->parts[0]);
        self::assertSame(['key' => 'val'], $result->parts[0]->value);
    }

    #[Test]
    public function parseDoesNotMutateDescriptor(): void
    {
        $intersection = IntersectionAggregate::of(TextType::class);
        $intersection->parse(['type' => 'text', 'value' => 'hello']);

        self::assertSame([], $intersection->parts);
    }

    #[Test]
    public function toStringReturnsJsonEncodedSchema(): void
    {
        $intersection = IntersectionAggregate::of(TextType::class, BinaryType::class);

        self::assertSame(json_encode($intersection->toJsonSchema()), (string)$intersection);
    }

    #[Test]
    public function toStringIsUsableInImplode(): void
    {
        $intersection = IntersectionAggregate::of(TextType::class);
        $result = implode("\n", ['prefix', $intersection]);

        self::assertStringContainsString('prefix', $result);
        self::assertStringContainsString('"allOf"', $result);
    }
}
