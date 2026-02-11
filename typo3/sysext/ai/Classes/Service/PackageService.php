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

namespace TYPO3\CMS\AI\Service;

use TYPO3\CMS\Core\Core\Environment;

/**
 * Service to interact with installed composer packages.
 */
final readonly class PackageService
{
    public const SYMFONY_AI_PLATFORM = 'symfony-ai-platform';

    private array $packages;

    public function __construct()
    {
        $path = Environment::getProjectPath() . '/vendor/composer/installed.json';
        $json = @file_get_contents($path);
        $data = $json !== false ? json_decode($json, true) : [];
        $this->packages = $data['packages'] ?? [];
    }

    public function isPackageInstalled(string $packageName): bool
    {
        foreach ($this->packages as $package) {
            if ($package['name'] === $packageName) {
                return true;
            }
        }
        return false;
    }

    public function getInstalledPackagesByType(string $type): array
    {
        $result = [];
        foreach ($this->packages as $package) {
            if (($package['type'] ?? '') === $type) {
                $result[] = $package['name'];
            }
        }
        return $result;
    }
}
