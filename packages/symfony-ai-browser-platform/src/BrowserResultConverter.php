<?php

namespace TYPO3\Symfony\AI\BrowserPlatform\Bridge;

use Symfony\AI\Platform\Model;
use Symfony\AI\Platform\Result\RawResultInterface;
use Symfony\AI\Platform\Result\ResultInterface;
use Symfony\AI\Platform\Result\TextResult;
use Symfony\AI\Platform\ResultConverterInterface;
use Symfony\AI\Platform\TokenUsage\TokenUsageExtractorInterface;

/**
 * Stub result converter for the browser platform.
 *
 * Converts the BrowserModelClient's stub response into a TextResult.
 * Only used during connection checks; normal inference is handled
 * client-side via @mlc-ai/web-llm.
 */
final class BrowserResultConverter implements ResultConverterInterface
{
    public function supports(Model $model): bool
    {
        return $model instanceof BrowserModel;
    }

    public function convert(RawResultInterface $result, array $options = []): ResultInterface
    {
        $data = $result->getData();
        return new TextResult($data['text'] ?? 'Browser inference available');
    }

    public function getTokenUsageExtractor(): ?TokenUsageExtractorInterface
    {
        return null;
    }
}
