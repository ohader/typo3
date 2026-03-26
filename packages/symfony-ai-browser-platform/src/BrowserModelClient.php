<?php

namespace TYPO3\Symfony\AI\BrowserPlatform\Bridge;

use Symfony\AI\Platform\Model;
use Symfony\AI\Platform\ModelClientInterface;
use Symfony\AI\Platform\Result\InMemoryRawResult;
use Symfony\AI\Platform\Result\RawResultInterface;

/**
 * Stub model client for the browser platform.
 *
 * This client is never invoked for actual inference — the AssistantOrchestrator
 * intercepts browser-platform requests and returns a BrowserDelegationResult
 * before the AgentGateway reaches this client.
 *
 * It is only called during connection checks, where it returns a harmless stub.
 */
final class BrowserModelClient implements ModelClientInterface
{
    public function supports(Model $model): bool
    {
        return $model instanceof BrowserModel;
    }

    public function request(Model $model, array|string $payload, array $options = []): RawResultInterface
    {
        return new InMemoryRawResult(['text' => 'Browser inference available']);
    }
}
