<?php

namespace TYPO3\Symfony\AI\BrowserPlatform\Bridge;

use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\AI\Platform\Contract;
use Symfony\AI\Platform\Platform;

/**
 * Creates a stub Platform for the browser LLM.
 *
 * The platform is never used for actual inference — the AssistantOrchestrator
 * short-circuits browser-platform requests and delegates to @mlc-ai/web-llm
 * running in the user's browser via WebGPU.
 *
 * This factory exists so that:
 * 1. PackageService::hasPackage() reports the platform as installed.
 * 2. PlatformConnector::buildBridge() can instantiate a bridge for availability checks.
 * 3. PlatformConnector::checkConnection() can verify the platform is "available".
 */
final class PlatformFactory
{
    public static function create(
        ?ModelCatalog $modelCatalog = null,
        ?EventDispatcherInterface $dispatcher = null,
    ): Platform {
        $modelCatalog ??= new ModelCatalog();

        return new Platform(
            [new BrowserModelClient()],
            [new BrowserResultConverter()],
            $modelCatalog,
            Contract::create(),
            $dispatcher,
        );
    }
}
