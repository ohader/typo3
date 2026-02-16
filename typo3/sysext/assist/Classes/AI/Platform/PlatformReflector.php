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

namespace TYPO3\CMS\Assist\AI\Platform;

use Symfony\AI\Platform\ModelCatalog\ModelCatalogInterface;
use Symfony\AI\Platform\PlatformInterface;

/**
 * @internal create instances via PackageService
 */
final readonly class PlatformReflector
{
    public function __construct(public string $namespace) {}

    /**
     * @return class-string<PlatformInterface>
     */
    public function getPlatformFactoryClassName(): string
    {
        return $this->namespace . 'PlatformFactory';
    }

    public function getPlatformFactoryOptions(array $options = []): array
    {
        $reflector = new \ReflectionMethod($this->getPlatformFactoryClassName(), 'create');
        return $this->resolveOptions($reflector, $options);
    }

    /**
     * @return class-string<ModelCatalogInterface>
     */
    public function getModelCatalogClassName(): string
    {
        return $this->namespace . 'ModelCatalog';
    }

    public function getModelCatalogOptions(array $options = []): array
    {
        $reflector = (new \ReflectionClass($this->getModelCatalogClassName()));
        return $this->resolveOptions($reflector, $options);
    }

    private function resolveOptions(\ReflectionClass|\ReflectionMethod $target, array $options = []): array
    {
        if ($target instanceof \ReflectionMethod) {
            return $this->prepareTargetArguments($target->getParameters(), $options);
        }
        if ($target->getConstructor() !== null) {
            return $this->prepareTargetArguments($target->getConstructor()->getParameters(), $options);
        }
        return [];
    }

    /**
     * @param list<\ReflectionParameter> $parameters
     */
    private function prepareTargetArguments(array $parameters, array $options): array
    {
        $args = [];
        foreach ($parameters as $parameter) {
            $name = $parameter->getName();
            if (array_key_exists($name, $options)) {
                $args[$name] = $options[$name];
            } elseif ($parameter->isDefaultValueAvailable()) {
                $args[$name] = $parameter->getDefaultValue();
            }
        }
        return $args;
    }
}
