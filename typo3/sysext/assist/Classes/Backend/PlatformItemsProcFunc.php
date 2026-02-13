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

namespace TYPO3\CMS\Assist\Backend;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Assist\Service\PackageService;

#[Autoconfigure(public: true)]
final readonly class PlatformItemsProcFunc
{
    public function __construct(private PackageService $packageService) {}

    public function getInstalledPlatformPackages(array &$parameters): void
    {
        /** @var array{value: string, label: string} $platformItems */
        $platformItems = array_map(
            static fn (string $name): array => ['value' => $name, 'label' => $name],
            $this->packageService->findPackageNamesByType(PackageService::SYMFONY_AI_PLATFORM)
        );
        $parameters['items'] = array_merge($parameters['items'] ?? [], $platformItems);
    }
}
