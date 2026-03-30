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
                'id' => 'msg_numb_1',
                'type' => 'message',
                'role' => 'assistant',
                'content' => [
                    [
                        'type' => 'tool_use',
                        'id' => 'numb_call_1',
                        'name' => $toolName,
                        'input' => [],
                    ],
                ],
                'stop_reason' => 'tool_use',
                'stop_sequence' => null,
                'usage' => [
                    'input_tokens' => 10,
                    'output_tokens' => 20,
                ],
            ]);
        }

        // Extract the last user message content for the echo response
        $lastUserMessage = '';
        foreach (array_reverse($body['messages'] ?? []) as $message) {
            if (($message['role'] ?? '') === 'user') {
                $content = $message['content'] ?? '';
                if (is_array($content)) {
                    // multimodal: pick the first text block
                    foreach ($content as $block) {
                        if (($block['type'] ?? '') === 'text') {
                            $content = $block['text'] ?? '';
                            break;
                        }
                    }
                }
                $lastUserMessage = is_string($content) ? $content : '';
                break;
            }
        }

        $responseContent = match ($lastUserMessage) {
            'ping' => 'pong',
            'Who are you?' => 'My name is Numb-Encore. Nice to meet you!',
            'What is TYPO3 in one sentence?' => 'TYPO3 is an open-source enterprise content management system (CMS) for building scalable websites and web applications.',
            'Give me exactly 3 numbered taglines for a headless CMS product. Return only the numbered list.' => "1. Your content, everywhere.\n2. Headless CMS, unlimited potential.\n3. Deliver content at the speed of thought.",
            'Return a JSON object describing Berlin with fields: city, country, population (integer), famous_landmark (string).' => '{"city":"Berlin","country":"Germany","population":3645000,"famous_landmark":"Brandenburg Gate"}',
            'Describe this image and suggest an appropriate alt text for it.' => 'The image shows a plain white rectangle with no visible content. Alt text: "Blank white image".',
            default => sprintf('I cannot help with "%s"', addcslashes($lastUserMessage, '"\\')),
        };

        return new InMemoryRawResult([
            'id' => 'msg_numb_1',
            'type' => 'message',
            'role' => 'assistant',
            'content' => [
                [
                    'type' => 'text',
                    'text' => $responseContent,
                ],
            ],
            'stop_reason' => 'end_turn',
            'stop_sequence' => null,
            'usage' => [
                'input_tokens' => 10,
                'output_tokens' => 20,
            ],
        ]);
    }
}
