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

namespace TYPO3\CMS\Core\Domain\MetaModel\Tca\Property;

interface RelationshipInterface
{
    public const DIRECTION_UNIDIRECTIONAL = 1;
    public const DIRECTION_BIDIRECTIONAL = 2;

    public const CARDINALITY_ONE_TO_ONE = 8;
    public const CARDINALITY_ONE_TO_MANY = 16;
    public const CARDINALITY_MANY_TO_MANY = 32;
    public const CARDINALITY_MANY_TO_ONE = 64;

    public const CASCADE_COPY = 256;
    public const CASCADE_LOCALIZE = 512;
    public const CASCADE_MOVE = 1024;
    public const CASCADE_DELETE = 2048;
    public const CASCADE_ALL = self::CASCADE_COPY | self::CASCADE_LOCALIZE | self::CASCADE_MOVE | self::CASCADE_DELETE;

    public function shallCascadeOn(int $flag): bool;
    public function isOfCardinality(int $flag): bool;
}
