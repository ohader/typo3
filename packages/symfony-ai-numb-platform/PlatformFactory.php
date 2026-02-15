<?php

namespace TYPO3\Symfony\AI\NumbPlatform\Bridge;

use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\AI\Platform\Contract;
use Symfony\AI\Platform\Platform;
use TYPO3\Symfony\AI\NumbPlatform\Bridge\Chat\ModelClient as ChatModelClient;
use TYPO3\Symfony\AI\NumbPlatform\Bridge\Chat\ResultConverter as ChatResultConverter;

final class PlatformFactory
{
    public static function create(
        ?ModelCatalog $modelCatalog = null,
        ?EventDispatcherInterface $dispatcher = null,
    ): Platform {
        $modelCatalog ??= new ModelCatalog();

        return new Platform(
            [new ChatModelClient()],
            [new ChatResultConverter()],
            $modelCatalog,
            Contract::create(),
            $dispatcher,
        );
    }
}
