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

use TYPO3\CMS\Core\Utility\GeneralUtility;

class EntityRelationMapFactory
{
    /**
     * @var array
     */
    protected $configuration;

    /**
     * @var string[]
     */
    protected $availableTableNames;

    /**
     * @var EntityDefinition[]
     */
    protected $entityDefinitions;

    public function __construct(array $configuration)
    {
        $configuration = array_filter(
            $configuration,
            function (array $tableConfiguration) {
                return !empty($tableConfiguration['columns']);
            }
        );

        if (empty($configuration)) {
            throw new \RuntimeException(
                'Provided configuration does not have any columns',
                1528993143
            );
        }

        $this->configuration = $configuration;
        $this->availableTableNames = array_keys($configuration);
        $this->entityDefinitions = $this->buildEntityDefinitions();
        foreach ($this->entityDefinitions as $entityDefinition) {
            $this->enrichRelations($entityDefinition);
        }
    }

    public function create(): EntityRelationMap
    {
        return GeneralUtility::makeInstance(
            EntityRelationMap::class,
            $this->entityDefinitions
        );
    }

    /**
     * First build all available entity definitions and properties.
     *
     * @return EntityDefinition[]
     */
    protected function buildEntityDefinitions(): array
    {
        $entityDefinitions = [];

        // first build all available entity definitions and properties
        foreach ($this->configuration as $tableName => $tableConfiguration) {
            $entityDefinitions = GeneralUtility::makeInstance(
                EntityDefinition::class,
                $tableName
            );

            foreach ($tableConfiguration['columns'] as $columnName => $columnConfiguration) {
                $entityDefinitions->addPropertyDefinition(
                    GeneralUtility::makeInstance(
                        PropertyDefinition::class,
                        $columnName,
                        $columnConfiguration
                    )
                );
            }

            $entityDefinitions[] = $entityDefinitions;
        }

        return $entityDefinitions;
    }

    /**
     * Analyzes types of relations.
     *
     * @param EntityDefinition $entityDefinition
     */
    protected function enrichRelations(EntityDefinition $entityDefinition) {
        foreach ($entityDefinition->getProperties() as $property) {
            $this->buildEntityDefinitionRelations($property);
        }
    }

    protected function buildEntityDefinitionRelations(PropertyDefinition $propertyDefinition)
    {
        $tableNames = $propertyDefinition->getRelationTableNames();
        if (empty($tableNames)) {
            return;
        }

        $configuration = $propertyDefinition->getConfiguration();

        foreach ($tableNames as $tableName) {
            $passiveEntityDefinition = $this->entityDefinitions[$tableName] ?? null;
            if ($passiveEntityDefinition === null) {
                continue;
            }

            $propertyDefinition->addRelation(
                GeneralUtility::makeInstance(
                    ActiveRelation::class,
                    $propertyDefinition,
                    $passiveEntityDefinition
                )
            );

            if (!$propertyDefinition->isInlineRelationProperty()
                || empty($configuration['config']['foreign_field'])
            ) {
                continue;
            }

            $foreignFieldName =  $configuration['config']['foreign_field'];
            $passiveProperty = $passiveEntityDefinition->getProperty($foreignFieldName);

            if ($passiveProperty === null) {
                $passiveProperty = GeneralUtility::makeInstance(
                    PropertyDefinition::class,
                    $foreignFieldName,
                    [] // no configuration, otherwise $passiveProperty would exist
                );
                $passiveEntityDefinition->addProperty($passiveProperty);
            }

            $passiveProperty->addRelation(
                GeneralUtility::makeInstance(
                    PassiveRelation::class,
                    $passiveProperty,
                    $propertyDefinition
                )
            );
        }
    }
}
