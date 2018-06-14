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

class PropertyDefinition
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var array
     */
    protected $configuration;

    /**
     * @var EntityDefinition
     */
    protected $entityDefinition;

    /**
     * @var ActiveRelation[]
     */
    protected $activeRelations = [];

    /**
     * @var PassiveRelation[]
     */
    protected $passiveRelations = [];

    public function __construct(string $name, array $configuration)
    {
        $this->name = $name;
        $this->configuration = $configuration;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    public function getEntityDefinition(): EntityDefinition
    {
        return $this->entityDefinition;
    }

    public function setEntityDefinition(EntityDefinition $entityDefinition)
    {
        if ($this->entityDefinition !== null) {
            throw new \RuntimeException('EntityDefinition is already defined', 1470497240);
        }
        $this->entityDefinition = $entityDefinition;
    }

    /**
     * @return Relational|ActiveRelation[]|PassiveRelation[]
     */
    public function getRelations(): array
    {
        return $this->activeRelations + $this->passiveRelations;
    }

    /**
     * @return ActiveRelation[]
     */
    public function getActiveRelations(): array
    {
        return $this->activeRelations;
    }

    public function hasActiveRelationTo(string $entityDefinitionName): bool
    {
        foreach ($this->activeRelations as $activeRelation) {
            if ($activeRelation->getTo()->getName() === $entityDefinitionName) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return PassiveRelation[]
     */
    public function getPassiveRelations(): array
    {
        return $this->passiveRelations;
    }

    public function hasPassiveRelationFrom(string $entityDefinitionName, string $propertyDefinitionName): bool
    {
        foreach ($this->passiveRelations as $passiveRelation) {
            if (
                $passiveRelation->getFrom()->getEntityDefinition() === $entityDefinitionName
                && $passiveRelation->getFrom()->getName() === $propertyDefinitionName
            ) {
                return true;
            }
        }
        return false;
    }

    public function addRelation(Relational $relation)
    {
        if ($relation->getPropertyDefinition() !== $this) {
            throw new \RuntimeException(
                'Cannot add relation of a different property definition',
                1528993144
            );
        }
        if ($relation instanceof ActiveRelation) {
            $this->activeRelations[] = $relation;
        }
        if ($relation instanceof PassiveRelation) {
            $this->passiveRelations[] = $relation;
        }
    }

    public function getPropertyType(): ?string
    {
        return $this->configuration['config']['type'] ?? null;
    }

    public function isRelationProperty(): bool
    {
        return $this->isInlineRelationProperty()
            || $this->isGroupDatabaseRelation()
            || $this->isSelectRelation()
            || $this->isLanguageRelationProperty();
    }

    public function getRelationTableNames(): array
    {
        // type=select, with special type languages
        if ($this->isLanguageRelationProperty()) {
            return ['sys_language'];
        // type=select, type=inline
        } elseif ($this->isSelectRelation() || $this->isInlineRelationProperty()) {
            return [$this->configuration['config']['foreign_table']];
        // type=group
        } elseif ($this->isGroupDatabaseRelation()) {
            $allowedTables = GeneralUtility::trimExplode(
                ',',
                $this->configuration['config']['allowed'],
                true
            );
            if (in_array('*', $allowedTables)) {
                return ['*'];
            } else {
                return $allowedTables;
            }
        }

        return [];
    }

    public function isInlineRelationProperty(): bool
    {
        return $this->getPropertyType() === 'inline'
            && !empty($this->configuration['config']['foreign_table']);
            // && @todo Check whether foreign_table exists
    }

    public function isGroupDatabaseRelation(): bool
    {
        return $this->getPropertyType() === 'group'
            && ($this->configuration['config']['internal_type'] ?? null) === 'db'
            && !empty($this->configuration['config']['allowed']);
    }

    public function isSelectRelation(): bool
    {
        return $this->getPropertyType() === 'select'
            && !empty($this->configuration['config']['foreign_table']);
            // && @todo Check whether foreign_table exists
    }

    public function isLanguageRelationProperty(): bool
    {
        return $this->getPropertyType() === 'select'
            && ($this->configuration['config']['special'] ?? null) === 'languages';
    }
}
