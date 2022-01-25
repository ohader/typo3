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

class SchemaDefinitionCollection
{
    /** @var array<string, SchemaDefinition> */
    protected array $schemaDefinitions = [];

    public function __construct(SchemaDefinition ...$schemaDefinitions)
    {
        $this->schemaDefinitions = array_combine(
            array_map(
                fn (SchemaDefinition $item): string => $item->getName(),
                $schemaDefinitions
            ),
            $schemaDefinitions,
        );
    }

    public function get(string $name): ?SchemaDefinition
    {
        return $this->schemaDefinitions[$name] ?? null;
    }
}
