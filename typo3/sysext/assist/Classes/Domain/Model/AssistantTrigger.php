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

namespace TYPO3\CMS\Assist\Domain\Model;

/**
 * @internal
 */
final readonly class AssistantTrigger
{
    /**
     * @param array<int, string> $resources
     * @param array<int, string> $components
     */
    public function __construct(
        public array $resources = [],
        public array $components = [],
    ) {}

    /**
     * @param array{resources?: list<mixed>, components?: list<mixed>} $configuration
     */
    public static function createFromConfiguration(array $configuration): self
    {
        return new self(
            resources: array_values(array_filter(array_map(strval(...), $configuration['resources'] ?? []))),
            components: array_values(array_filter(array_map(strval(...), $configuration['components'] ?? []))),
        );
    }

    public function hasResource(string $resource): bool
    {
        return in_array($resource, $this->resources, true);
    }

    public function hasComponent(string $component): bool
    {
        return in_array($component, $this->components, true);
    }
}
