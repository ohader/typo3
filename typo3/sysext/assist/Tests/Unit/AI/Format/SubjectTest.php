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
use TYPO3\CMS\Assist\AI\Format\Subject;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class SubjectTest extends UnitTestCase
{
    #[Test]
    public function getTypeReturnsSubject(): void
    {
        self::assertSame('subject', Subject::getType());
    }

    #[Test]
    public function toJsonSchemaReturnsExpectedStructure(): void
    {
        $schema = Subject::toJsonSchema();

        self::assertSame('object', $schema['type']);
        self::assertSame('subject', $schema['properties']['type']['const']);
        self::assertSame('string', $schema['properties']['value']['type']);
        self::assertSame(['type', 'value'], $schema['required']);
        self::assertFalse($schema['additionalProperties']);
    }

    #[Test]
    public function toJsonSchemaValuePropertyHasDescription(): void
    {
        $schema = Subject::toJsonSchema();

        self::assertArrayHasKey('description', $schema['properties']['value']);
    }

    #[Test]
    public function fromJsonCreatesInstanceWithValue(): void
    {
        $encoded = '{"table":"pages","uid":1,"field":"title"}';
        $subject = Subject::fromJson(['type' => 'subject', 'value' => $encoded]);

        self::assertInstanceOf(Subject::class, $subject);
        self::assertSame($encoded, $subject->value);
    }
}
