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
     * @var EntityRelationMap
     */
    protected $entityRelationMap;

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
    }

    public function create(): EntityRelationMap
    {
        $this->entityRelationMap = GeneralUtility::makeInstance(
            EntityRelationMap::class,
            ...$this->buildEntityDefinitions()
        );

        foreach ($this->entityRelationMap->getEntityDefinitions() as $entityDefinition) {
            $this->enrichRelations($entityDefinition);
        }

        return $this->entityRelationMap;
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
            $entityDefinition = GeneralUtility::makeInstance(
                EntityDefinition::class,
                $tableName
            );

            foreach ($tableConfiguration['columns'] as $columnName => $columnConfiguration) {
                $entityDefinition->addPropertyDefinition(
                    GeneralUtility::makeInstance(
                        PropertyDefinition::class,
                        $columnName,
                        $columnConfiguration
                    )
                );
            }

            $entityDefinitions[] = $entityDefinition;
        }

        return $entityDefinitions;
    }

    /**
     * Analyzes types of relations.
     *
     * @param EntityDefinition $entityDefinition
     */
    protected function enrichRelations(EntityDefinition $entityDefinition) {
        foreach ($entityDefinition->getPropertyDefinitions() as $propertyDefinitions) {
            $this->buildEntityDefinitionRelations($propertyDefinitions);
        }
    }

    protected function buildEntityDefinitionRelations(PropertyDefinition $propertyDefinition)
    {
        $tableNames = $propertyDefinition->getRelationTableNames();
        if (empty($tableNames)) {
            return;
        }
        if ($tableNames === ['*']) {
            $tableNames = $this->availableTableNames;
        }

        $configuration = $propertyDefinition->getConfiguration();

        foreach ($tableNames as $tableName) {
            $passiveEntityDefinition = $this->entityRelationMap
                ->getEntityDefinition($tableName);
            if ($passiveEntityDefinition === null) {
                continue;
            }

            $propertyDefinition->addRelation(
                GeneralUtility::makeInstance(
                    ActiveEntityRelation::class,
                    $propertyDefinition,
                    $passiveEntityDefinition
                )
            );

            // add passive property relation to inline pointer property
            if ($propertyDefinition->isInlineRelationProperty()
                && !empty($configuration['config']['foreign_field'])
            ) {
                $this->buildPassivePropertyRelation(
                    $propertyDefinition,
                    $passiveEntityDefinition,
                    $configuration['config']['foreign_field']
                );
            }
        }
    }

    protected function buildPassivePropertyRelation(
        PropertyDefinition $propertyDefinition,
        EntityDefinition $passiveEntityDefinition,
        string $passivePropertyName)
    {
        $passiveProperty = $passiveEntityDefinition->getProperty($passivePropertyName);

        if ($passiveProperty === null) {
            $passiveProperty = GeneralUtility::makeInstance(
                PropertyDefinition::class,
                $passivePropertyName,
                // no configuration, otherwise $passiveProperty would exist
                // as it has been defined in $this->configuration
                []
            );
            $passiveEntityDefinition->addPropertyDefinition($passiveProperty);
        }

        $passiveProperty->addRelation(
            GeneralUtility::makeInstance(
                PassivePropertyRelation::class,
                $passiveProperty,
                $propertyDefinition
            )
        );
    }
}
