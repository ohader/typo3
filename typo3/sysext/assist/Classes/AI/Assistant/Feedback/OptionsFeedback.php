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

final readonly class OptionsFeedback implements FeedbackInterface
{
    /**
     * @param list<OptionItem> $options
     */
    public function __construct(
        public string $key,
        public string $text,
        public array $options,
        public string $view = 'list',
        public ?string $heading = null,
        public ?string $image = null,
        public ?string $video = null,
    ) {
        $count = count($options);
        if ($count < 2 || $count > 4) {
            throw new \InvalidArgumentException(
                'OptionsFeedback requires between 2 and 4 options, ' . $count . ' given.',
                1773749607,
            );
        }
        if ($image !== null && $video !== null) {
            throw new \InvalidArgumentException(
                'OptionsFeedback cannot have both image and video set.',
                1773749608,
            );
        }
    }

    public function getValue(): string
    {
        return $this->text;
    }

    public function jsonSerialize(): array
    {
        return [
            'type' => 'options',
            'key' => $this->key,
            'text' => $this->text,
            'options' => $this->options,
            'view' => $this->view,
            'heading' => $this->heading,
            'image' => $this->image,
            'video' => $this->video,
        ];
    }
}
