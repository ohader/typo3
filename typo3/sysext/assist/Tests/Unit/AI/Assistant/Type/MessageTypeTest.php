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
use TYPO3\CMS\Assist\AI\Assistant\Type\TextType;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class MessageTypeTest extends UnitTestCase
{
    #[Test]
    public function getTypeReturnsMessage(): void
    {
        self::assertSame('text', TextType::getType());
    }

    #[Test]
    public function toJsonSchemaReturnsExpectedStructure(): void
    {
        $schema = TextType::toJsonSchema();

        self::assertSame('object', $schema['type']);
        self::assertSame('text', $schema['properties']['type']['const']);
        self::assertSame('string', $schema['properties']['value']['type']);
        self::assertSame(['type', 'value'], $schema['required']);
        self::assertFalse($schema['additionalProperties']);
    }

    #[Test]
    public function fromJsonCreatesInstanceWithValue(): void
    {
        $message = TextType::fromJson(['type' => 'text', 'value' => 'hello world']);

        self::assertInstanceOf(TextType::class, $message);
        self::assertSame('hello world', $message->value);
    }
}
