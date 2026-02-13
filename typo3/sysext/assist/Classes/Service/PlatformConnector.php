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
use Symfony\AI\Platform\Capability;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use TYPO3\CMS\Assist\Domain\Platform;
use TYPO3\CMS\Assist\Domain\PlatformBridge;
use TYPO3\CMS\Assist\Event\BeforeBuildPlatformBridgeEvent;

/**
 * Creates live Symfony AI Platform instances from TYPO3 Platform domain objects
 * and tests connections by invoking a minimal prompt.
 *
 * @internal
 */
final readonly class PlatformConnector
{
    public function __construct(
        private PackageService $packageService,
        private EventDispatcherInterface $eventDispatcher,
    ) {}


    /**
     * @param bool $effective Whether to apply platform/model filtering (if applicable)
     */
    public function buildBridge(Platform $platform, bool $effective = true): PlatformBridge
    {
        $packageName = $platform->package;
        $package = $this->packageService->getPackage($packageName);
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

        $event = new BeforeBuildPlatformBridgeEvent($platform, $namespace, $effective, $options);
        $this->eventDispatcher->dispatch($event);

        return new PlatformBridge($platform, $namespace, $effective, $event->getOptions());
    }
    /**
     * Creates a live PlatformInterface instance from a TYPO3 Platform domain object.
     */
    /**
     * Tests the connection by picking the first text-capable model and invoking a minimal prompt.
     *
     * @return array{success: true}
     * @throws \Throwable on connection or invocation failure
     */
    public function checkConnection(Platform $platform): array
    {
        $bridge = $this->buildBridge($platform);
        $livePlatform = $bridge->getPlatformFactory();
        // @todo use local catalog, which might be more specific (overridden)
        // $catalog = $livePlatform->getModelCatalog();
        $catalog = $bridge->getModelCatalog();
        $models = $catalog->getModels();

        $targetModel = null;
        foreach ($models as $name => $meta) {
            $capabilities = $meta['capabilities'] ?? [];
            $hasInput = false;
            $hasOutput = false;
            foreach ($capabilities as $cap) {
                if ($cap === Capability::INPUT_MESSAGES) {
                    $hasInput = true;
                }
                if ($cap === Capability::OUTPUT_TEXT) {
                    $hasOutput = true;
                }
            }
            if ($hasInput && $hasOutput) {
                $targetModel = $name;
                break;
            }
        }

        if ($targetModel === null) {
            throw new \RuntimeException('No text-capable model found in the platform catalog.', 1739400001);
        }

        try {
            $result = $livePlatform->invoke($targetModel, new MessageBag(Message::ofUser('ping')));
            // Force resolution to verify the connection end-to-end
            $result->asText();
        } catch (\Throwable $e) {
            throw new \RuntimeException(
                sprintf(
                    'Connection check failed for platform "%s" using model "%s": %s',
                    $platform->name,
                    $targetModel,
                    $e->getMessage(),
                ),
                1739400002,
                $e,
            );
        }

        return ['success' => true];
    }
}
