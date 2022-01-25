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

namespace TYPO3\CMS\Core\Domain\MetaModel\Tca;

use TYPO3\CMS\Core\Domain\MetaModel\Tca\Property\IdlePropertyDefinition;
use TYPO3\CMS\Core\Domain\MetaModel\Tca\Property\PropertyFactoryInterface;
use TYPO3\CMS\Core\Domain\MetaModel\Tca\Property\PropertyInterface;
use TYPO3\CMS\Core\Domain\MetaModel\Tca\Property\RelationshipPropertyDefinition;
use TYPO3\CMS\Core\Domain\MetaModel\Tca\Property\ScalarPropertyDefinition;

class SchemaDefinitionFactory
{
    /**
     * @todo should be extensible via global system configuration (e.g. custom types)
     * @var array<string, class-string<PropertyFactoryInterface>>
     */
    private const PROPERTY_DEFINITIONS = [
        'category' => RelationshipPropertyDefinition::class,
        'check' => null,
        'flex' => null,
        'group' => RelationshipPropertyDefinition::class,
        'imageManipulation' => null,
        'inline' => RelationshipPropertyDefinition::class,
        'input' => ScalarPropertyDefinition::class,
        'language' => RelationshipPropertyDefinition::class,
        'none' => IdlePropertyDefinition::class,
        'passthrough' => IdlePropertyDefinition::class,
        'radio' => null,
        'select' => ScalarPropertyDefinition::class,
        'slug' => null,
        'text' => ScalarPropertyDefinition::class,
        'user' => null,
    ];

    public function buildCollectionFromConfiguration(array $configuration): SchemaDefinitionCollection
    {
        $tableNames = array_keys($configuration);
        /** @var list<SchemaDefinition> $schemaDefinitions */
        $schemaDefinitions = array_map(
            fn (string $name, array $configuration) => $this->buildDefinitionFromConfiguration($name, $configuration),
            $tableNames,
            $GLOBALS['TCA']
        );
        return new SchemaDefinitionCollection(...$schemaDefinitions);
    }

    public function buildDefinitionFromConfiguration(string $name, array $configuration): SchemaDefinition
    {
        $properties = $configuration['columns'] ?? [];
        $aspects = $this->buildAspects($configuration);

        $subject = new SchemaDefinition($name);
        if ($properties !== []) {
            $subject = $subject->withProperties(
                ...array_map(
                    [$this, 'buildProperty'],
                    array_keys($properties),
                    array_values($properties)
                )
            );
        }
        if ($aspects !== []) {
            $subject = $subject->withAspects(...$aspects);
        }
        return $subject;
    }

    protected function buildProperty(string $name, array $configuration): PropertyInterface
    {
        $type = (string)($configuration['config']['type'] ?? '');
        $factoryClassName = self::PROPERTY_DEFINITIONS[$type] ?? ScalarPropertyDefinition::class;
        if (!is_a($factoryClassName, PropertyFactoryInterface::class, true)) {
            throw new \LogicException(
                sprintf('Invalid property factory declaration for type "%s"', $type),
                1644231988
            );
        }
        return $factoryClassName::buildProperty($name, $type, $configuration);
    }

    protected function buildAspects(array $configuration): array
    {
        $aspects = [];
        return $aspects;
    }
}
