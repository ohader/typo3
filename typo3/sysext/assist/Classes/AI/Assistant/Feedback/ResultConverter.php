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

namespace TYPO3\CMS\Assist\AI\Assistant\Feedback;

use Symfony\AI\Platform\Result\ChoiceResult;
use Symfony\AI\Platform\Result\ResultInterface;
use Symfony\AI\Platform\Result\TextResult;
use TYPO3\CMS\Assist\AI\Agent\BrowserDelegationResult;

final readonly class ResultConverter
{
    /**
     * Tries to convert a result into a feedback object.
     * In case the response contains a JSON response reflecting one of the supported items in the
     * `TYPO3\CMS\Assist\AI\Assistant\Type` namespace, the associated options are returned:
     *
     * Otherwise, the conversion falls back to mapping the Symfony AI default results to generic
     * objects (TextResult, OptionsFeedback, OptionsFeedback).
     */
    public function convert(ResultInterface $result): FeedbackInterface
    {
        if ($result instanceof BrowserDelegationResult) {
            return new BrowserInferenceFeedback(
                assistantIdentifier: $result->assistantIdentifier,
                model: $result->model,
                messages: $result->messages,
                tools: $result->tools,
                suppressThinking: $result->suppressThinking,
                responseSchema: $result->responseSchema,
                modelOptions: $result->modelOptions,
            );
        }

        if ($result instanceof TextResult) {
            $content = $result->getContent();
            $json = json_decode($content, true);
            if (is_array($json)) {
                $feedback = $this->convertFromType($json);
                if ($feedback !== null) {
                    return $feedback;
                }
            }
            return new TextFeedback($content);
        }

        if ($result instanceof ChoiceResult) {
            $choices = $result->getContent();
            $options = [];
            foreach ($choices as $index => $choice) {
                $content = $choice->getContent();
                $options[] = new OptionItem(
                    identifier: (string)$index,
                    text: is_string($content) ? $content : (json_encode($content) ?: ''),
                );
            }
            return new OptionsFeedback(
                key: bin2hex(random_bytes(8)),
                text: 'Choose one of the following options:',
                options: $options,
            );
        }

        $content = $result->getContent();
        $text = is_string($content) ? $content : (json_encode($content) ?: '');
        return new TextFeedback($text);
    }

    private function convertFromType(array $json): ?FeedbackInterface
    {
        $type = $json['type'] ?? null;

        if ($type === 'text' && isset($json['value']) && is_string($json['value'])) {
            return new TextFeedback($json['value']);
        }

        if ($type === 'markdown' && isset($json['value']) && is_string($json['value'])) {
            return new MarkdownFeedback($json['value']);
        }

        if ($type === 'options' && isset($json['items']) && is_array($json['items']) && count($json['items']) >= 2 && count($json['items']) <= 4) {
            $options = [];
            foreach ($json['items'] as $index => $item) {
                $options[] = new OptionItem(
                    identifier: (string)$index,
                    text: (isset($item['value']) && is_string($item['value'])) ? $item['value'] : (json_encode($item) ?: ''),
                );
            }
            return new OptionsFeedback(
                key: bin2hex(random_bytes(8)),
                text: 'Choose one of the following options:',
                options: $options,
            );
        }

        if ($type === 'list' && isset($json['items']) && is_array($json['items'])) {
            $items = [];
            foreach ($json['items'] as $item) {
                if (isset($item['value']) && is_string($item['value'])) {
                    $items[] = $item['value'];
                } elseif (is_string($item)) {
                    $items[] = $item;
                }
            }
            if ($items !== []) {
                return new ListFeedback($items);
            }
        }

        return null;
    }
}
