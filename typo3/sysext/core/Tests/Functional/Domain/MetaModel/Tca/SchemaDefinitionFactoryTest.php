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

namespace TYPO3\CMS\Core\Tests\Functional\Domain\MetaModel\Tca;

use TYPO3\CMS\Core\Domain\MetaModel\Tca\SchemaDefinitionFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case
 */
class SchemaDefinitionFactoryTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function coreSchemaDefinitionsPassesSimpleTest(): void
    {
        $factory = new SchemaDefinitionFactory();
        $collection = $factory->buildCollectionFromConfiguration($GLOBALS['TCA']);
        self::assertSame('tt_content', $collection->get('tt_content')->getName());
    }

    public static function relationshipPropertyDefinitionIsCreatedDataProvider(): array
    {
        return [
            [

            ]
        ];
    }

    public function relationshipPropertyDefinitionIsCreated(): void
    {

    }
}
