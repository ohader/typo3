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

namespace TYPO3\CMS\Core\Domain\MetaModel\Tca\Display;

class DisplayType
{
    use ParsingTrait;

    /**
     * @var list<FieldRef|PaletteRef|TabRef>
     */
    protected array $showItems = [];
    protected array $columnsOverrides = [];
    protected ?SubTypes $subTypes = null;
    protected ?Bitmask $bitmask = null;

    public static function fromArray(string $name, array $data): self
    {
        $target = (new self($name))
            ->withShowItems(...self::parseTypeShowItems($data['showitem'] ?? ''));
        // @todo parse & apply remaining properties
        return $target;
    }

    public function __construct(protected string $name) {}

    public function withShowItems(FieldRef|PaletteRef|TabRef ...$showItems): self
    {
        $target = clone $this;
        $target->showItems = $showItems;
        return $target;
    }

    // @todo `FieldChangeDefinition`-like
    public function withColumnsOverrides($columnsOverrides): self
    {
        $target = clone $this;
        $target->columnsOverrides = $columnsOverrides;
        return $this;
    }

    public function withSubTypes(SubTypes $subTypes): self
    {
        $target = clone $this;
        $target->subTypes = $subTypes;
        return $this;
    }

    public function withBitmask(Bitmask $bitmask): self
    {
        $target = clone $this;
        $target->bitmask = $bitmask;
        return $target;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return list<FieldRef|PaletteRef|TabRef>
     */
    public function getShowItems(): array
    {
        return $this->showItems;
    }
}
