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

namespace TYPO3\CMS\Assist;

use Psr\Container\ContainerInterface;
use TYPO3\CMS\Assist\Domain\Assistant;
use TYPO3\CMS\Assist\Service\AssistantRegistry;
use TYPO3\CMS\Core\Cache\Event\CacheWarmupEvent;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Package\AbstractServiceProvider;
use TYPO3\CMS\Core\Package\Cache\PackageDependentCacheIdentifier;

/**
 * @internal
 */
class ServiceProvider extends AbstractServiceProvider
{
    protected static function getPackagePath(): string
    {
        return __DIR__ . '/../';
    }

    protected static function getPackageName(): string
    {
        return 'typo3/cms-assist';
    }

    public function getFactories(): array
    {
        return [
            AssistantRegistry::class => self::getAssistantRegistry(...),
            'backend.assistants' => self::getBackendAssistants(...),
            'backend.assistants.warmer' => self::getBackendAssistantsWarmer(...),
        ];
    }

    public function getExtensions(): array
    {
        return [
            ListenerProvider::class => self::addEventListeners(...),
        ] + parent::getExtensions();
    }

    public static function getAssistantRegistry(ContainerInterface $container): AssistantRegistry
    {
        $cache = $container->get('cache.core');
        $cacheIdentifier = $container->get(PackageDependentCacheIdentifier::class)->withPrefix('BackendAssistants')->toString();
        $assistantsFromPackages = $cache->require($cacheIdentifier);
        if ($assistantsFromPackages === false) {
            $assistantsFromPackages = $container->get('backend.assistants')->getArrayCopy();
            $cache->set($cacheIdentifier, 'return ' . var_export($assistantsFromPackages, true) . ';');
        }

        foreach ($assistantsFromPackages as $identifier => $configuration) {
            $assistantsFromPackages[$identifier] = Assistant::createFromConfiguration($identifier, $configuration);
        }

        return self::new($container, AssistantRegistry::class, [$assistantsFromPackages]);
    }

    public static function getBackendAssistants(ContainerInterface $container): \ArrayObject
    {
        return new \ArrayObject();
    }

    public static function getBackendAssistantsWarmer(ContainerInterface $container): \Closure
    {
        return static function (CacheWarmupEvent $event) use ($container) {
            if ($event->hasGroup('system')) {
                $cache = $container->get('cache.core');
                $cacheIdentifier = $container->get(PackageDependentCacheIdentifier::class)->withPrefix('BackendAssistants')->toString();
                $assistantsFromPackages = $container->get('backend.assistants')->getArrayCopy();
                $cache->set($cacheIdentifier, 'return ' . var_export($assistantsFromPackages, true) . ';');
            }
        };
    }

    public static function addEventListeners(ContainerInterface $container, ListenerProvider $listenerProvider): ListenerProvider
    {
        $listenerProvider->addListener(CacheWarmupEvent::class, 'backend.assistants.warmer');

        return $listenerProvider;
    }
}
