<?php

namespace TYPO3\Symfony\AI\NumbPlatform\Bridge\Chat;

use Symfony\AI\Platform\Model;
use Symfony\AI\Platform\Result\RawResultInterface;
use Symfony\AI\Platform\Result\ResultInterface;
use Symfony\AI\Platform\Result\TextResult;
use Symfony\AI\Platform\Result\ToolCall;
use Symfony\AI\Platform\Result\ToolCallResult;
use Symfony\AI\Platform\ResultConverterInterface;
use Symfony\AI\Platform\TokenUsage\TokenUsageExtractorInterface;
use TYPO3\Symfony\AI\NumbPlatform\Bridge\ChatModel;

final class ResultConverter implements ResultConverterInterface
{
    public function __construct(
        private readonly TokenUsageExtractor $tokenUsageExtractor = new TokenUsageExtractor(),
    ) {
    }

    public function supports(Model $model): bool
    {
        return $model instanceof ChatModel;
    }

    public function convert(RawResultInterface $result, array $options = []): ResultInterface
    {
        $data = $result->getData();
        $contentItems = $data['content'] ?? [];

        $toolUseItems = array_values(array_filter($contentItems, static fn($item) => ($item['type'] ?? '') === 'tool_use'));
        if ($toolUseItems !== []) {
            return $this->createToolCallResult($toolUseItems);
        }

        foreach ($contentItems as $item) {
            if (($item['type'] ?? '') === 'text') {
                return new TextResult($item['text'] ?? '');
            }
        }

        return new TextResult('');
    }

    public function getTokenUsageExtractor(): ?TokenUsageExtractorInterface
    {
        return $this->tokenUsageExtractor;
    }

    /**
     * @param array<int, array<string, mixed>> $toolCalls
     */
    private function createToolCallResult(array $toolCalls): ToolCallResult
    {
        $calls = [];

        foreach ($toolCalls as $toolCall) {
            $arguments = $toolCall['input'] ?? [];

            if (\is_string($arguments)) {
                $arguments = json_decode($arguments, true, flags: \JSON_THROW_ON_ERROR);
            }

            $calls[] = new ToolCall(
                id: $toolCall['id'],
                name: $toolCall['name'],
                arguments: $arguments,
            );
        }

        return new ToolCallResult(...$calls);
    }
}
