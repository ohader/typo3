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

class EntityDefinition
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var PropertyDefinition[]
     */
    protected $propertyDefinitions = [];

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function __toString(): string
    {
        return $this->name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return PropertyDefinition[]
     */
    public function getPropertyDefinitions(): array
    {
        return $this->propertyDefinitions;
    }

    public function addPropertyDefinition(PropertyDefinition $propertyDefinition)
    {
        if ($this->hasProperty($propertyDefinition->getName())) {
            throw new \RuntimeException(
                sprintf(
                    'Property "%s" is already defined',
                    $propertyDefinition->getName()
                ),
                1470496497
            );
        }
        $propertyDefinition->setEntityDefinition($this);
        $this->propertyDefinitions[$propertyDefinition->getName()] = $propertyDefinition;
    }

    public function hasProperty($propertyName): bool
    {
        return isset($this->propertyDefinitions[$propertyName]);
    }

    /**
     * @param string $propertyName
     * @return null|PropertyDefinition
     */
    public function getPropertyDefinition(?string $propertyName)
    {
        return ($this->propertyDefinitions[$propertyName] ?? null);
    }
}
