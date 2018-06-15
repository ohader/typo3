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

class EntityRelationMap
{
    /**
     * @var EntityDefinition[]
     */
    protected $entityDefinitions = [];

    public function __construct(EntityDefinition ...$entityDefinitions)
    {
        foreach ($entityDefinitions as $entityDefinition) {
            $this->entityDefinitions[$entityDefinition->getName()] = $entityDefinition;
        }
    }

    /**
     * @param string $name
     * @return null|EntityDefinition
     */
    public function getEntityDefinition(string $name): ?EntityDefinition {
        return ($this->entityDefinitions[$name] ?? null);
    }

    /**
     * @return EntityDefinition[]
     */
    public function getEntityDefinitions()
    {
        return $this->entityDefinitions;
    }

    /**
     * @param bool $filter Whether to filter out items without relations
     * @return array
     */
    public function export(bool $filter = false): array
    {
        $entityDefinitions = array_map(
            function (EntityDefinition $entityDefinition) use ($filter) {
                return $this->exportEntityDefinition(
                    $entityDefinition,
                    $filter
                );
            },
            $this->entityDefinitions
        );

        if ($filter) {
            $entityDefinitions = array_filter($entityDefinitions, 'count');
        }

        return $entityDefinitions;
    }

    protected function exportEntityDefinition(EntityDefinition $entityDefinition, bool $filter = false): array
    {
        $propertyDefinitions = array_map(
            function (PropertyDefinition $propertyDefinition) use ($filter) {
                return $this->exportPropertyDefinition(
                    $propertyDefinition,
                    $filter
                );
            },
            $entityDefinition->getPropertyDefinitions()
        );

        if ($filter) {
            $propertyDefinitions = array_filter($propertyDefinitions, 'count');
        }

        return $propertyDefinitions;
    }

    protected function exportPropertyDefinition(PropertyDefinition $propertyDefinition, bool $filter): array
    {
        $relations = [
            'active' => array_map('strval', $propertyDefinition->getActiveRelations()),
            'passive' => array_map('strval', $propertyDefinition->getPassiveRelations()),
        ];

        if ($filter) {
            $relations = array_filter($relations, 'count');
        }

        return $relations;
    }
}
