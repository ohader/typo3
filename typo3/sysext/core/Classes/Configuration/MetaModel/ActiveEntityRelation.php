<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Configuration\MetaModel;

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

class ActiveEntityRelation implements Relational
{
    /**
     * @var PropertyDefinition
     */
    protected $propertyDefinition;

    /**
     * @var EntityDefinition
     */
    protected $to;

    public function __construct(PropertyDefinition $propertyDefinition, EntityDefinition $to)
    {
        $this->propertyDefinition = $propertyDefinition;
        $this->to = $to;
    }

    public function __toString(): string
    {
        return $this->propertyDefinition . ' -> ' . $this->to;
    }

    public function getPropertyDefinition(): PropertyDefinition
    {
        return $this->propertyDefinition;
    }

    public function getTo(): EntityDefinition
    {
        return $this->to;
    }
}
