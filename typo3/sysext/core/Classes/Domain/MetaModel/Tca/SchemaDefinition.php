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

use TYPO3\CMS\Core\Domain\MetaModel\Tca\Property\PropertyInterface;

class SchemaDefinition
{
    /** @var array<string, PropertyInterface> */
    protected array $properties = [];

    /** @var array<class-string, AspectInterface> */
    protected array $aspects = [];

    public function __construct(protected string $name)
    {
    }

    public function withProperties(PropertyInterface ...$properties): self
    {
        $subject = clone $this;
        $subject->properties = array_combine(
            array_map(fn (PropertyInterface $property): string => $property->getName(), $properties),
            $properties
        );
        return $subject;
    }

    public function withAspects(AspectInterface ...$aspects): self
    {
        $subject = clone $this;
        $subject->aspects = array_combine(
            array_map('get_class', $aspects),
            $aspects
        );
        return $subject;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array<string, PropertyInterface>
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * @return array<class-string, AspectInterface>
     */
    public function getAspects(): array
    {
        return $this->aspects;
    }

    /**
     * @param class-string $name
     */
    public function getAspect(string $name): ?AspectInterface
    {
        return $this->aspects[$name] ?? null;
    }
}
