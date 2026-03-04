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
use TYPO3\CMS\Assist\AI\Format\Message;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class MessageTest extends UnitTestCase
{
    #[Test]
    public function getTypeReturnsMessage(): void
    {
        self::assertSame('message', Message::getType());
    }

    #[Test]
    public function toJsonSchemaReturnsExpectedStructure(): void
    {
        $schema = Message::toJsonSchema();

        self::assertSame('object', $schema['type']);
        self::assertSame('message', $schema['properties']['type']['const']);
        self::assertSame('string', $schema['properties']['value']['type']);
        self::assertSame(['type', 'value'], $schema['required']);
        self::assertFalse($schema['additionalProperties']);
    }

    #[Test]
    public function fromJsonCreatesInstanceWithValue(): void
    {
        $message = Message::fromJson(['type' => 'message', 'value' => 'hello world']);

        self::assertInstanceOf(Message::class, $message);
        self::assertSame('hello world', $message->value);
    }
}
