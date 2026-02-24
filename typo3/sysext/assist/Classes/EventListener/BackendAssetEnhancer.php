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

namespace TYPO3\CMS\Assist\EventListener;

use TYPO3\CMS\Backend\Controller\Event\BeforeBackendPageRenderEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;

/**
 * Loads assist JavaScript modules into the backend top frame.
 */
#[AsEventListener('typo3/cms-assist/backend-asset-enhancer')]
final readonly class BackendAssetEnhancer
{
    public function __invoke(BeforeBackendPageRenderEvent $event): void
    {
        $event->javaScriptRenderer->addJavaScriptModuleInstruction(
            JavaScriptModuleInstruction::create('@typo3/assist/sidebar/assist-chat-panel.js')
        );
    }
}
