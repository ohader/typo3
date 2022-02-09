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

class FieldRef implements \Stringable
{
    public static function fromString(string $data): self
    {
        $parts = GeneralUtility::trimExplode(';', $data);
        return new self($parts[0], $parts[1] ?? '');
    }

    public function __construct(protected string $name, protected string $label = '') {}

    public function __toString(): string
    {
        return $this->name . ($this->label !== '' ? ';' . $this->label : '');
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        return $this->label;
    }
}
