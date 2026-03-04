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
use TYPO3\CMS\Assist\AI\Format\Structure;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class StructureTest extends UnitTestCase
{
    #[Test]
    public function getTypeReturnsStructure(): void
    {
        self::assertSame('structure', Structure::getType());
    }

    #[Test]
    public function toJsonSchemaReturnsExpectedStructure(): void
    {
        $schema = Structure::toJsonSchema();

        self::assertSame('object', $schema['type']);
        self::assertSame('structure', $schema['properties']['type']['const']);
        self::assertSame('object', $schema['properties']['value']['type']);
        self::assertSame(['type', 'value'], $schema['required']);
        self::assertFalse($schema['additionalProperties']);
    }

    #[Test]
    public function fromJsonCreatesInstanceWithValue(): void
    {
        $data = ['city' => 'Berlin', 'country' => 'Germany', 'population' => 3645000];
        $structure = Structure::fromJson(['type' => 'structure', 'value' => $data]);

        self::assertInstanceOf(Structure::class, $structure);
        self::assertSame($data, $structure->value);
    }
}
