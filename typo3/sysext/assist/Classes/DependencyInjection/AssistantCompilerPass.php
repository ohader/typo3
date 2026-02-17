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

/**
 * @internal
 */
final class AssistantCompilerPass implements CompilerPassInterface
{
    public function __construct(
        private readonly string $tagName,
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
                        'types' => json_decode($attributes['triggerTypes'], true),
                        'records' => json_decode($attributes['triggerRecords'], true),
                        'components' => json_decode($attributes['triggerComponents'], true),
                    ],
                    'tools' => json_decode($attributes['tools'], true),
                ];
            }
        }

        $registryDefinition->setArgument('$assistants', $assistants);
    }
}
