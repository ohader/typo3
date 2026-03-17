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
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class BinaryTypeTest extends UnitTestCase
{
    #[Test]
    public function getTypeReturnsBinary(): void
    {
        self::assertSame('binary', BinaryType::getType());
    }

    #[Test]
    public function toJsonSchemaReturnsExpectedStructure(): void
    {
        $schema = BinaryType::toJsonSchema();

        self::assertSame('object', $schema->jsonSerialize()['type']);
        self::assertSame('binary', $schema->jsonSerialize()['properties']['type']['const']);
        self::assertSame('string', $schema->jsonSerialize()['properties']['data']['type']);
        self::assertSame('base64', $schema->jsonSerialize()['properties']['data']['contentEncoding']);
        self::assertSame('string', $schema->jsonSerialize()['properties']['mimeType']['type']);
        self::assertSame(['type', 'data'], $schema->jsonSerialize()['required']);
        self::assertFalse($schema->jsonSerialize()['additionalProperties']);
    }

    #[Test]
    public function fromJsonCreatesInstanceWithDataAndMimeType(): void
    {
        $binary = BinaryType::fromJson(['type' => 'binary', 'data' => 'abc=', 'mimeType' => 'image/png']);

        self::assertInstanceOf(BinaryType::class, $binary);
        self::assertSame('abc=', $binary->data);
        self::assertSame('image/png', $binary->mimeType);
    }

    #[Test]
    public function fromJsonSetsNullMimeTypeWhenAbsent(): void
    {
        $binary = BinaryType::fromJson(['type' => 'binary', 'data' => 'abc=']);

        self::assertInstanceOf(BinaryType::class, $binary);
        self::assertSame('abc=', $binary->data);
        self::assertNull($binary->mimeType);
    }
}
