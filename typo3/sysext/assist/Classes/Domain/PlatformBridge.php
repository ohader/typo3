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

namespace TYPO3\CMS\Assist\Domain;

use Symfony\AI\Platform\ModelCatalog\ModelCatalogInterface;
use Symfony\AI\Platform\PlatformInterface;
use TYPO3\CMS\Assist\Exception\PlatformNotAvailableException;

/**
 * @internal create instances via PackageService
 */
final readonly class PlatformBridge
{
    public function __construct(
        private Platform $platform,
        private string $namespace,
        private bool $effective,
        private array $options = [],
    ) {
        if ($this->effective && $this->platform->availability !== Availability::enabled) {
            throw new PlatformNotAvailableException(
                'Platform ' . $this->platform->name . ' is not available.',
                1771009690
            );
        }
    }

    public function getPlatformFactory(): PlatformInterface
    {
        $class = $this->namespace . 'PlatformFactory';
        return $this->instantiate([$class, 'create'], 'platformFactory');
    }

    public function getModelCatalog(): ModelCatalogInterface
    {
        $class = $this->namespace . 'ModelCatalog';
        $modelCatalog = $this->instantiate($class, 'modelCatalog');
        return $this->effective && $this->platform->models !== []
            ? new FilteredModelCatalog($this->platform, $modelCatalog)
            : $modelCatalog;
    }

    /**
     * @param string|array{0: string, 1: string} $target
     */
    private function instantiate(string|array $target, string $optionKey): PlatformInterface|ModelCatalogInterface
    {
        $options = $this->options[$optionKey] ?? [];

        if (is_array($target)) {
            $parameters = (new \ReflectionMethod($target[0], $target[1]))->getParameters();
            $args = $this->prepareTargetArguments($parameters, $options);
            return call_user_func(implode('::', $target), ...$args);
        }

        $constructor = (new \ReflectionClass($target))->getConstructor();
        if ($constructor === null) {
            return new $target();
        }
        $args = $this->prepareTargetArguments($constructor->getParameters(), $options);
        return new $target(...$args);
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
