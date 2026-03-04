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
use TYPO3\CMS\Assist\AI\Assistant\Type\OptionsAggregate;
use TYPO3\CMS\Assist\AI\Assistant\Type\StructureType;
use TYPO3\CMS\Assist\AI\Assistant\Type\SubjectType;
use TYPO3\CMS\Assist\AI\Assistant\Type\TextType;
use TYPO3\CMS\Assist\AI\Assistant\Type\UnionAggregate;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class UnionAggregateTest extends UnitTestCase
{
    #[Test]
    public function toJsonSchemaWithSingleTypeReturnsOneOfWithOneEntry(): void
    {
        $schema = UnionAggregate::of(TextType::class)->toJsonSchema();

        self::assertArrayHasKey('oneOf', $schema);
        self::assertCount(1, $schema['oneOf']);
        self::assertSame(TextType::toJsonSchema(), $schema['oneOf'][0]);
    }

    #[Test]
    public function toJsonSchemaWithMultipleTypesIncludesAllSchemas(): void
    {
        $schema = UnionAggregate::of(TextType::class, BinaryType::class, StructureType::class)->toJsonSchema();

        self::assertArrayHasKey('oneOf', $schema);
        self::assertCount(3, $schema['oneOf']);
        self::assertSame(TextType::toJsonSchema(), $schema['oneOf'][0]);
        self::assertSame(BinaryType::toJsonSchema(), $schema['oneOf'][1]);
        self::assertSame(StructureType::toJsonSchema(), $schema['oneOf'][2]);
    }

    #[Test]
    public function toJsonSchemaContainsNoExtraKeys(): void
    {
        $schema = UnionAggregate::of(TextType::class)->toJsonSchema();

        self::assertSame(['oneOf'], array_keys($schema));
    }

    #[Test]
    public function toJsonSchemaWithCompositeTypeIncludesItsSchema(): void
    {
        $options = OptionsAggregate::of(TextType::class);
        $schema = UnionAggregate::of(TextType::class, $options)->toJsonSchema();

        self::assertCount(2, $schema['oneOf']);
        self::assertSame(TextType::toJsonSchema(), $schema['oneOf'][0]);
        self::assertSame($options->toJsonSchema(), $schema['oneOf'][1]);
    }

    #[Test]
    public function parseDispatchesToMessageType(): void
    {
        $result = UnionAggregate::of(TextType::class, BinaryType::class)
            ->parse(['type' => 'text', 'value' => 'hello']);

        self::assertInstanceOf(TextType::class, $result);
        self::assertSame('hello', $result->value);
    }

    #[Test]
    public function parseDispatchesToBinaryType(): void
    {
        $result = UnionAggregate::of(TextType::class, BinaryType::class)
            ->parse(['type' => 'binary', 'data' => 'abc=', 'mimeType' => 'image/png']);

        self::assertInstanceOf(BinaryType::class, $result);
        self::assertSame('abc=', $result->data);
        self::assertSame('image/png', $result->mimeType);
    }

    #[Test]
    public function parseDispatchesToLaterTypeInList(): void
    {
        $result = UnionAggregate::of(TextType::class, BinaryType::class, SubjectType::class)
            ->parse(['type' => 'subject', 'value' => '{"table":"pages","uid":1}']);

        self::assertInstanceOf(SubjectType::class, $result);
    }

    #[Test]
    public function parseDispatchesToCompositeOptionsAggregate(): void
    {
        $options = OptionsAggregate::of(TextType::class);
        $result = UnionAggregate::of(TextType::class, $options)
            ->parse([
                'type' => 'options',
                'items' => [
                    ['type' => 'text', 'value' => 'Yes'],
                    ['type' => 'text', 'value' => 'No'],
                ],
            ]);

        self::assertInstanceOf(OptionsAggregate::class, $result);
        self::assertCount(2, $result->items);
    }

    #[Test]
    public function parseThrowsUnexpectedValueExceptionForUnknownType(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1740999601);
        $this->expectExceptionMessage('Unknown output format type "unknown".');

        UnionAggregate::of(TextType::class, BinaryType::class)->parse(['type' => 'unknown', 'value' => 'x']);
    }

    #[Test]
    public function parseThrowsUnexpectedValueExceptionWhenTypeKeyMissing(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1740999601);

        UnionAggregate::of(TextType::class)->parse(['value' => 'x']);
    }

    #[Test]
    public function toStringReturnsJsonEncodedSchema(): void
    {
        $union = UnionAggregate::of(TextType::class, BinaryType::class);

        self::assertSame(json_encode($union->toJsonSchema()), (string)$union);
    }

    #[Test]
    public function toStringIsUsableInImplode(): void
    {
        $union = UnionAggregate::of(TextType::class);
        $result = implode("\n", ['prefix', $union]);

        self::assertStringContainsString('prefix', $result);
        self::assertStringContainsString('"oneOf"', $result);
    }
}
