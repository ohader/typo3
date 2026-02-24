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

namespace TYPO3\CMS\Assist\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use TYPO3\CMS\Assist\Service\AssistantRegistry;
use TYPO3\CMS\Core\Package\PackageManager;

/**
 * @internal
 */
final readonly class AssistantCompilerPass implements CompilerPassInterface
{
    public function __construct(
        private string $tagName,
        private PackageManager $packageManager,
    ) {}

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(AssistantRegistry::class)) {
            return;
        }
        $registryDefinition = $container->findDefinition(AssistantRegistry::class);
        $assistants = [];

        foreach ($container->findTaggedServiceIds($this->tagName) as $serviceName => $tags) {
            foreach ($tags as $attributes) {
                $identifier = $attributes['identifier'];
                $assistants[$identifier] = [
                    'mode' => $attributes['mode'],
                    'capabilities' => json_decode($attributes['capabilities'], true),
                    'handler' => $serviceName,
                    'trigger' => [
                        'resources' => json_decode($attributes['triggerResources'], true),
                        'components' => json_decode($attributes['triggerComponents'], true),
                        'routes' => json_decode($attributes['triggerRoutes'], true),
                    ],
                    'label' => $this->resolveLabel($serviceName, $identifier, $attributes['labelFile'] ?? ''),
                    'javaScriptModule' => $attributes['javaScriptModule'] ?? '',
                ];
            }
        }

        $registryDefinition->setArgument('$assistants', $assistants);
    }

    private function resolveLabel(string $handlerClass, string $identifier, string $labelFile): string
    {
        if ($labelFile !== '') {
            return 'LLL:' . $labelFile . ':default';
        }

        $classFile = (new \ReflectionClass($handlerClass))->getFileName();
        foreach ($this->packageManager->getActivePackages() as $package) {
            if (str_starts_with($classFile, $package->getPackagePath())) {
                return 'LLL:EXT:' . $package->getPackageKey()
                    . '/Resources/Private/Language/Assistants/' . $identifier . '.xlf:default';
            }
        }

        return '';
    }
}
