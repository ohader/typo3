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

namespace TYPO3\CMS\Assist\Service;

use Symfony\Component\Config\Resource\ComposerResource;

/**
 * Service to interact with installed composer packages.
 */
final readonly class PackageService
{
    public const SYMFONY_AI_PLATFORM = 'symfony-ai-platform';

    /**
     * @var array<string, array>
     */
    private array $packages;

    public function __construct(
        private ComposerResource $composerResource,
    )
    {
        $allPackages = [];
        foreach ($this->composerResource->getVendors() as $vendorDir) {
            $path = $vendorDir . '/composer/installed.json';
            $json = @file_get_contents($path);
            $data = $json !== false ? json_decode($json, true) : [];
            $packages = array_filter(
                $data['packages'] ?? [],
                static fn (array $package): bool => is_string($package['name'] ?? null)
            );
            $names = array_column($packages, 'name');
            $allPackages += array_combine($names, $packages);
        }
        $this->packages = $allPackages;
    }

    public function hasPackage(string $packageName): bool
    {
        return isset($this->packages[$packageName]);
    }

    public function getPackage(string $packageName): ?array
    {
        return $this->packages[$packageName] ?? null;

    }

    public function getPackageNamespace(string $packageName): ?string
    {
        $package = $this->getPackage($packageName);
        if ($package === null) {
            throw new \InvalidArgumentException(
                sprintf('Package "%s" is not installed.', $packageName),
                1771173561
            );
        }

        $psr4 = $package['autoload']['psr-4'] ?? [];
        $namespace = array_key_first($psr4);
        if ($namespace !== null) {
            return $namespace;
        }
        throw new \InvalidArgumentException(
            sprintf('Package "%s" does not declare a PSR-4 autoload namespace.', $packageName),
            1771173571
        );
    }

    /**
     * @return list<string>
     */
    public function findPackageNamesByType(string $type): array
    {
        $names = array_keys(
            array_filter(
                $this->packages,
                static fn (array $package) => ($package['type'] ?? null) === $type
            )
        );
        return array_values($names);
    }
}
