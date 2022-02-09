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

use TYPO3\CMS\Core\Utility\GeneralUtility;

class PaletteRef
{
    public static function fromString(string $item): self
    {
        $parts = GeneralUtility::trimExplode(';', $item);
        if ($parts[0] !== '--palette--' || !isset($parts[2])) {
            throw new \LogicException('Cannot parse palette reference', 1644412120);
        }
        return new self($parts[1], $parts[2]);
    }

    public function __construct(protected string $label, protected string $name) {}

    public function __toString(): string
    {
        return '--palette--;' . $this->label . ';' . $this->name;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
