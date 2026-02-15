<?php

namespace TYPO3\Symfony\AI\NumbPlatform\Bridge\Chat;

use Symfony\AI\Platform\Model;
use Symfony\AI\Platform\ModelClientInterface;
use Symfony\AI\Platform\Result\InMemoryRawResult;
use Symfony\AI\Platform\Result\RawResultInterface;
use TYPO3\Symfony\AI\NumbPlatform\Bridge\ChatModel;

final class ModelClient implements ModelClientInterface
{
    public function supports(Model $model): bool
    {
        return $model instanceof ChatModel;
    }

    public function request(Model $model, array|string $payload, array $options = []): RawResultInterface
    {
        $body = array_merge($options, \is_array($payload) ? $payload : []);

        // If tools are provided, return a tool call response picking the first tool
        if (!empty($body['tools'])) {
            $firstTool = $body['tools'][0];
            $toolName = $firstTool['function']['name'] ?? 'unknown';

            return new InMemoryRawResult([
                'choices' => [
                    [
                        'message' => [
                            'content' => null,
                            'tool_calls' => [
                                [
                                    'id' => 'numb_call_1',
                                    'type' => 'function',
                                    'function' => [
                                        'name' => $toolName,
                                        'arguments' => '{}',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'usage' => [
                    'prompt_tokens' => 10,
                    'completion_tokens' => 20,
                    'total_tokens' => 30,
                ],
            ]);
        }

        // Extract the last user message content for the echo response
        $lastUserMessage = '';
        foreach (array_reverse($body['messages'] ?? []) as $message) {
            if (($message['role'] ?? '') === 'user') {
                $lastUserMessage = $message['content'] ?? '';
                break;
            }
        }

        return new InMemoryRawResult([
            'choices' => [
                [
                    'message' => [
                        'content' => 'numb-encore received: ' . $lastUserMessage,
                    ],
                ],
            ],
            'usage' => [
                'prompt_tokens' => 10,
                'completion_tokens' => 20,
                'total_tokens' => 30,
            ],
        ]);
    }
}
