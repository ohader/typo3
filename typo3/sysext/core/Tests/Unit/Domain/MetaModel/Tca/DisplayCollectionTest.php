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

namespace TYPO3\CMS\Core\Tests\Unit\Domain\MetaModel\Tca;

use TYPO3\CMS\Core\Domain\MetaModel\Tca\Display\DisplayType;
use TYPO3\CMS\Core\Domain\MetaModel\Tca\DisplayCollection;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class DisplayCollectionTest extends UnitTestCase
{
    /**
     * @test
     */
    public function fieldNamesAreResolved(): void
    {
        $configuration = [
            'types' => [
                'type-a' => [
                    'showitem' => 'a, b;label-b, --div--;tab-tabel, --palette--;palette-label;palette-c, f',
                ],
            ],
            'palettes' => [
                'palette-c' => [
                    'showitem' => 'd, --linebreak--, e;label-e'
                ],
            ],
        ];

        $collection = DisplayCollection::fromConfiguration(
            $configuration['types'],
            $configuration['palettes']
        );

        $typeA = $collection->getType('type-a');
        self::assertInstanceOf(DisplayType::class, $typeA);
        self::assertSame(['a', 'b', 'd', 'e', 'f'], $collection->resolveTypeFieldNames($typeA));
    }
}
