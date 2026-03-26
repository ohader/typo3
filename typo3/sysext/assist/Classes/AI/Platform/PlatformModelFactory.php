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

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Assist\Event\AfterBuildPlatformModelEvent;

/**
 * Creates {@see PlatformModel} instances and dispatches {@see AfterBuildPlatformModelEvent}
 * so that listeners can enrich the model (e.g. mark it as local).
 */
final readonly class PlatformModelFactory
{
    public function __construct(private EventDispatcherInterface $eventDispatcher) {}

    public function create(string $platform, string $model): PlatformModel
    {
        $target = new PlatformModel($platform, $model);
        $event = new AfterBuildPlatformModelEvent($target);
        $this->eventDispatcher->dispatch($event);
        return $event->getPlatformModel();
    }

    public function fromString(string $identifier): PlatformModel
    {
        $parts = explode('@', $identifier, 2);
        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            throw new \InvalidArgumentException(
                sprintf('Invalid platform model identifier "%s", expected format "model@platform".', $identifier),
                1771165071
            );
        }
        return $this->create(platform: $parts[1], model: $parts[0]);
    }
}
