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
use TYPO3\CMS\Assist\AI\Assistant\Type\SubjectType;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class SubjectTypeTest extends UnitTestCase
{
    #[Test]
    public function getTypeReturnsSubject(): void
    {
        self::assertSame('subject', SubjectType::getType());
    }

    #[Test]
    public function toJsonSchemaReturnsExpectedStructure(): void
    {
        $schema = SubjectType::toJsonSchema();

        self::assertSame('object', $schema->jsonSerialize()['type']);
        self::assertSame('subject', $schema->jsonSerialize()['properties']['type']['const']);
        self::assertSame('string', $schema->jsonSerialize()['properties']['value']['type']);
        self::assertSame(['type', 'value'], $schema->jsonSerialize()['required']);
        self::assertFalse($schema->jsonSerialize()['additionalProperties']);
    }

    #[Test]
    public function toJsonSchemaValuePropertyHasDescription(): void
    {
        $schema = SubjectType::toJsonSchema();

        self::assertArrayHasKey('description', $schema->jsonSerialize()['properties']['value']);
    }

    #[Test]
    public function fromJsonCreatesInstanceWithValue(): void
    {
        $encoded = '{"table":"pages","uid":1,"field":"title"}';
        $subject = SubjectType::fromJson(['type' => 'subject', 'value' => $encoded]);

        self::assertInstanceOf(SubjectType::class, $subject);
        self::assertSame($encoded, $subject->value);
    }
}
