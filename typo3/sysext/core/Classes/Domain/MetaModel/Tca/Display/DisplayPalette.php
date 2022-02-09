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

class DisplayPalette
{
    use ParsingTrait;

    /**
     * @var list<FieldRef|LinebreakRef>
     */
    protected array $showItems = [];
    protected ?string $label = null;
    protected ?string $description = null;
    protected bool $hiddenPalette = false;

    public static function fromArray(string $name, array $data): self
    {
        $target = (new self($name))
            ->withShowItems(...self::parsePaletteShowItem($data['showitem'] ?? ''))
            ->withHiddenPalette((bool)($data['isHiddenPalette'] ?? false));
        if (isset($data['label'])) {
            $target = $target->withLabel((string)$data['label']);
        }
        if (isset($data['description'])) {
            $target = $target->withDescription((string)$data['description']);
        }
        return $target;
    }

    public function __construct(protected string $name) {}

    public function withShowItems(FieldRef|LinebreakRef ...$showItems): self
    {
        $target = clone $this;
        $target->showItems = $showItems;
        return $target;
    }

    public function withLabel(?string $label): self
    {
        $target = clone $this;
        $target->label = $label;
        return $target;
    }

    public function withDescription(?string $description): self
    {
        $target = clone $this;
        $target->description = $description;
        return $target;
    }

    public function withHiddenPalette(bool $hiddenPalette): self
    {
        $target = clone $this;
        $target->hiddenPalette = $hiddenPalette;
        return $target;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return list<FieldRef|LinebreakRef>
     */
    public function getShowItems(): array
    {
        return $this->showItems;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function isHiddenPalette(): bool
    {
        return $this->hiddenPalette;
    }
}
