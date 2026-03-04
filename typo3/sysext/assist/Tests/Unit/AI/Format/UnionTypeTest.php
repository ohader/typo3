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
use TYPO3\CMS\Assist\AI\Format\Structure;
use TYPO3\CMS\Assist\AI\Format\Subject;
use TYPO3\CMS\Assist\AI\Format\UnionType;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class UnionTypeTest extends UnitTestCase
{
    #[Test]
    public function toJsonSchemaWithSingleTypeReturnsOneOfWithOneEntry(): void
    {
        $schema = UnionType::of(Message::class)->toJsonSchema();

        self::assertArrayHasKey('oneOf', $schema);
        self::assertCount(1, $schema['oneOf']);
        self::assertSame(Message::toJsonSchema(), $schema['oneOf'][0]);
    }

    #[Test]
    public function toJsonSchemaWithMultipleTypesIncludesAllSchemas(): void
    {
        $schema = UnionType::of(Message::class, Binary::class, Structure::class)->toJsonSchema();

        self::assertArrayHasKey('oneOf', $schema);
        self::assertCount(3, $schema['oneOf']);
        self::assertSame(Message::toJsonSchema(), $schema['oneOf'][0]);
        self::assertSame(Binary::toJsonSchema(), $schema['oneOf'][1]);
        self::assertSame(Structure::toJsonSchema(), $schema['oneOf'][2]);
    }

    #[Test]
    public function toJsonSchemaContainsNoExtraKeys(): void
    {
        $schema = UnionType::of(Message::class)->toJsonSchema();

        self::assertSame(['oneOf'], array_keys($schema));
    }

    #[Test]
    public function parseDispatchesToMessageType(): void
    {
        $result = UnionType::of(Message::class, Binary::class)
            ->parse(['type' => 'message', 'value' => 'hello']);

        self::assertInstanceOf(Message::class, $result);
        self::assertSame('hello', $result->value);
    }

    #[Test]
    public function parseDispatchesToBinaryType(): void
    {
        $result = UnionType::of(Message::class, Binary::class)
            ->parse(['type' => 'binary', 'data' => 'abc=', 'mimeType' => 'image/png']);

        self::assertInstanceOf(Binary::class, $result);
        self::assertSame('abc=', $result->data);
        self::assertSame('image/png', $result->mimeType);
    }

    #[Test]
    public function parseDispatchesToLaterTypeInList(): void
    {
        $result = UnionType::of(Message::class, Binary::class, Subject::class)
            ->parse(['type' => 'subject', 'value' => '{"table":"pages","uid":1}']);

        self::assertInstanceOf(Subject::class, $result);
    }

    #[Test]
    public function parseThrowsUnexpectedValueExceptionForUnknownType(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1740999601);
        $this->expectExceptionMessage('Unknown output format type "unknown".');

        UnionType::of(Message::class, Binary::class)->parse(['type' => 'unknown', 'value' => 'x']);
    }

    #[Test]
    public function parseThrowsUnexpectedValueExceptionWhenTypeKeyMissing(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1740999601);

        UnionType::of(Message::class)->parse(['value' => 'x']);
    }
}
