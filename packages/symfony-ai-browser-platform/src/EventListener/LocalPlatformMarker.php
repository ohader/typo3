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

namespace TYPO3\Symfony\AI\BrowserPlatform\Bridge\EventListener;

use TYPO3\CMS\Assist\AI\Platform\PlatformModel;
use TYPO3\CMS\Assist\Event\AfterBuildPlatformModelEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;

/**
 * Marks browser-platform models as local ({@see PlatformModel::$isLocal}),
 * so that {@see \TYPO3\CMS\Assist\AI\Assistant\AssistantOrchestrator} delegates
 * inference to the browser instead of calling a remote model API.
 */
#[AsEventListener('typo3/symfony-ai-browser-platform/mark-local')]
final readonly class LocalPlatformMarker
{
    public function __invoke(AfterBuildPlatformModelEvent $event): void
    {
        $model = $event->getPlatformModel();
        if ($model->platform !== 'typo3/symfony-ai-browser-platform') {
            return;
        }
        $event->setPlatformModel(new PlatformModel(
            platform: $model->platform,
            model: $model->model,
            isLocal: true,
        ));
    }
}
