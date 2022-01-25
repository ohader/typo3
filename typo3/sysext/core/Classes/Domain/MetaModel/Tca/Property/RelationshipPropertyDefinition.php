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

class RelationshipPropertyDefinition implements PropertyInterface, RelationshipInterface, PropertyFactoryInterface
{
    protected const CARDINALITY_MASK = 7;

    protected ?RelationshipTarget $target = null;
    protected ?RelationshipConstraint $constraint = null;

    public static function buildProperty(string $name, string $type, array $configuration): PropertyInterface
    {
        $cascade = 0;
        $cardinality = 0;
        if ($type === 'inline') {
            $cascade |= RelationshipInterface::CASCADE_ALL;
        }



        $target = new self($name, $type, $cardinality | $cascade);
        return $target;
    }

    protected static function buildConstraint(string $type, array $configuration): RelationshipConstraint
    {
        $minimum = (int)($configuration['config']['minitems'] ?? 0);
        $maximum = (int)($configuration['config']['maxitems'] ?? 0);

        $target = new RelationshipConstraint();
        if ($minimum >= 0) {
            $target = $target->withMinimum($minimum);
        }
        if ($maximum > 0) {
            $target = $target->withMaximum($maximum);
        }
        return $target;
    }

    public function __construct(protected string $name, protected string $type, protected int $flags)
    {
    }

    public function withTarget(RelationshipTarget $target): self
    {
        $subject = clone $this;
        $subject->target = $target;
        return $subject;
    }

    public function withConstraint(RelationshipConstraint $constraint): self
    {
        $subject = clone $this;
        $subject->constraint = $constraint;
        return $subject;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getFlags(): int
    {
        return $this->flags;
    }

    public function getTarget(): ?RelationshipTarget
    {
        return $this->target;
    }

    public function getConstraint(): ?RelationshipConstraint
    {
        return $this->constraint;
    }

    public function shallCascadeOn(int $flag): bool
    {
        return ($this->flags &~ self::CARDINALITY_MASK & $flag) === $flag;
    }

    public function isOfCardinality(int $flag): bool
    {
        return ($this->flags & self::CARDINALITY_MASK & $flag) === $flag;
    }
}
