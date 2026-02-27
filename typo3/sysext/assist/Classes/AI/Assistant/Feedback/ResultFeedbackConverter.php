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

final readonly class ResultFeedbackConverter
{
    public function convert(ResultInterface $result): FeedbackInterface
    {
        if ($result instanceof TextResult) {
            return new MessageFeedback($result->getContent());
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
        return new MessageFeedback($text);
    }
}
