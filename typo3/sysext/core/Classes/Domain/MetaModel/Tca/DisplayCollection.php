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

use TYPO3\CMS\Core\Domain\MetaModel\Tca\Display\DisplayPalette;
use TYPO3\CMS\Core\Domain\MetaModel\Tca\Display\DisplayType;
use TYPO3\CMS\Core\Domain\MetaModel\Tca\Display\FieldRef;
use TYPO3\CMS\Core\Domain\MetaModel\Tca\Display\PaletteRef;

class DisplayCollection
{
    /**
     * @var array<string, DisplayType>
     */
    protected array $types = [];
    /**
     * @var array<string, DisplayPalette>
     */
    protected array $palettes = [];

    public static function fromConfiguration(array $types, array $palettes): self
    {
        $typesNames = array_keys($types);
        $palettesNames = array_keys($palettes);

        $target = new self();
        $target->types = array_combine(
            $typesNames,
            array_map(
                fn (string $name, array $item) => DisplayType::fromArray($name, $item),
                $typesNames,
                $types
            )
        );
        $target->palettes = array_combine(
            $palettesNames,
            array_map(
                fn (string $name, array $item) => DisplayPalette::fromArray($name, $item),
                $palettesNames,
                $palettes
            )
        );
        return $target;
    }

    public function getType(string $name): ?DisplayType
    {
        return $this->types[$name] ?? null;
    }

    public function getPalette(string $name): ?DisplayPalette
    {
        return $this->palettes[$name] ?? null;
    }

    /**
     * @param DisplayType $type
     * @return list<FieldRef>
     */
    public function resolveTypeFieldRefs(DisplayType $type): array
    {
        if (!in_array($type, $this->types, true)) {
            throw new \LogicException(
                sprintf('Unknown type reference "%s"', $type->getName()),
                1644425384
            );
        }
        $fieldRefs = [];
        // @todo looks ugly
        foreach ($type->getShowItems() as $item) {
            if ($item instanceof FieldRef) {
                $fieldRefs[] = $item;
            } elseif ($item instanceof PaletteRef) {
                $palette = $this->getPalette($item->getName());
                if ($palette === null) {
                    throw new \LogicException(
                        sprintf('Unknown palette reference "%s"', $item->getName()),
                        1644425842
                    );
                }
                foreach ($palette->getShowItems() as $paletteItem) {
                    if ($paletteItem instanceof FieldRef) {
                        $fieldRefs[] = $paletteItem;
                    }
                }
            }
        }
        return $fieldRefs;
    }

    /**
     * @param DisplayType $type
     * @return list<string>
     */
    public function resolveTypeFieldNames(DisplayType $type): array
    {
        return array_map(
            fn (FieldRef $item) => $item->getName(),
            $this->resolveTypeFieldRefs($type)
        );
    }
}
