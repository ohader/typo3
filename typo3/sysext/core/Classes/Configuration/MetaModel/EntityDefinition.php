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
    protected $properties = [];

    public function __construct(string $name)
    {
        $this->name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return PropertyDefinition[]
     */
    public function getProperties(): array
    {
        return $this->properties;
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
        $this->properties[$propertyDefinition->getName()] = $propertyDefinition;
    }

    public function hasProperty($propertyName): bool
    {
        return isset($this->properties[$propertyName]);
    }

    /**
     * @param string $propertyName
     * @return null|PropertyDefinition
     */
    public function getProperty(string $propertyName)
    {
        return ($this->properties[$propertyName] ?? null);
    }
}
