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

namespace TYPO3\CMS\Core\Domain\MetaModel\Tca\Property;

class RelationshipConstraint
{
    protected ?int $minimum = null;
    protected ?int $maximum = null;
    protected ?array $values = null;

    public function __construct()
    {
    }

    public function withMinimum(int $minimum): self
    {
        if ($minimum < 0) {
            throw new \LogicException('Minimum cannot be negative', 1644236766);
        }
        $target = clone $this;
        $target->minimum = $minimum;
        return $target;
    }

    public function withMaximum(int $maximum): self
    {
        if ($maximum < 1) {
            throw new \LogicException('Maximum must be greater than zero', 1644236796);
        }
        $target = clone $this;
        $target->maximum = $maximum;
        return $target;
    }

    public function withValues(array $values): self
    {
        $target = clone $this;
        $target->values = $values;
        return $target;
    }

    public function getMinimum(): ?int
    {
        return $this->minimum;
    }

    public function getMaximum(): ?int
    {
        return $this->maximum;
    }

    public function getValues(): ?array
    {
        return $this->values;
    }
}
