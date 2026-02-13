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

use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Config\Resource\ComposerResource;
use TYPO3\CMS\Assist\Domain\Platform;
use TYPO3\CMS\Assist\Domain\PlatformBridge;
use TYPO3\CMS\Assist\Event\BeforeBuildPlatformBridgeEvent;

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
        private EventDispatcherInterface $eventDispatcher,
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

    public function buildBridge(Platform $platform): PlatformBridge
    {
        $packageName = $platform->package;
        $package = $this->packages[$packageName] ?? null;
        if ($package === null) {
            throw new \InvalidArgumentException(
                sprintf('Package "%s" is not installed.', $packageName),
            );
        }

        $psr4 = $package['autoload']['psr-4'] ?? [];
        $namespace = array_key_first($psr4);
        if ($namespace === null) {
            throw new \InvalidArgumentException(
                sprintf('Package "%s" does not declare a PSR-4 autoload namespace.', $packageName),
            );
        }

        $options = [
            'platformFactory' => [
                'hostUrl' => $platform->options['baseUrl'] ?? '',
                'apiKey' => $platform->authorization?->token ?? '',
            ],
        ];

        $event = new BeforeBuildPlatformBridgeEvent($platform, $namespace, $options);
        $this->eventDispatcher->dispatch($event);

        return new PlatformBridge($namespace, $event->getOptions());
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
