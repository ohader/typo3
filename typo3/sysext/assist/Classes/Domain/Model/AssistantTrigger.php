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
     * @param list<string> $resources Resources which shall use this assistant, e.g. `['pages', 'tt_content']`
     * @param list<string> $components Backend components which shall use this assistant, e.g. `['page-tree', 'context-menu']`
     * @param list<string> $routes Backend routes which shall use this assistant, e.g. `['/module/web/layout']`
     */
    public function __construct(
        public array $resources = [],
        public array $components = [],
        public array $routes = [],
    ) {}

    /**
     * @param array{resources?: list<mixed>, components?: list<mixed>, routes?: list<mixed>} $configuration
     */
    public static function createFromConfiguration(array $configuration): self
    {
        return new self(
            resources: array_values(array_filter(array_map(strval(...), $configuration['resources'] ?? []))),
            components: array_values(array_filter(array_map(strval(...), $configuration['components'] ?? []))),
            routes: array_values(array_filter(array_map(strval(...), $configuration['routes'] ?? []))),
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

    public function hasRoute(string $route): bool
    {
        return in_array($route, $this->routes, true);
    }
}
