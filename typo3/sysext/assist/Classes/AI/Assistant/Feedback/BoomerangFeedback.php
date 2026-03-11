<?php

declare(strict_types=1);

namespace TYPO3\CMS\Assist\AI\Assistant\Feedback;

final readonly class BoomerangFeedback implements FeedbackInterface
{
    public function __construct(
        public array $params = [],
    ) {}

    public function getText(): string
    {
        return '';
    }

    public function jsonSerialize(): array
    {
        return [
            'type' => 'boomerang',
            'params' => $this->params,
        ];
    }
}
