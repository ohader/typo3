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

namespace TYPO3\CMS\Assist\AI\Message;

use Symfony\AI\Platform\TokenUsage\TokenUsageInterface;

final readonly class TokenConsumption
{
    public function __construct(
        public ?int $promptTokens = null,
        public ?int $completionTokens = null,
        public ?int $totalTokens = null,
        public ?int $thinkingTokens = null,
        public ?int $toolTokens = null,
        public ?int $cachedTokens = null,
        public ?int $cacheCreationTokens = null,
        public ?int $cacheReadTokens = null,
    ) {}

    public static function fromTokenUsageInterface(TokenUsageInterface $usage): self
    {
        return new self(
            promptTokens: $usage->getPromptTokens(),
            completionTokens: $usage->getCompletionTokens(),
            totalTokens: $usage->getTotalTokens(),
            thinkingTokens: $usage->getThinkingTokens(),
            toolTokens: $usage->getToolTokens(),
            cachedTokens: $usage->getCachedTokens(),
            cacheCreationTokens: $usage->getCacheCreationTokens(),
            cacheReadTokens: $usage->getCacheReadTokens(),
        );
    }

    public function add(self $other): self
    {
        return new self(
            promptTokens: $this->addNullable($this->promptTokens, $other->promptTokens),
            completionTokens: $this->addNullable($this->completionTokens, $other->completionTokens),
            totalTokens: $this->addNullable($this->totalTokens, $other->totalTokens),
            thinkingTokens: $this->addNullable($this->thinkingTokens, $other->thinkingTokens),
            toolTokens: $this->addNullable($this->toolTokens, $other->toolTokens),
            cachedTokens: $this->addNullable($this->cachedTokens, $other->cachedTokens),
            cacheCreationTokens: $this->addNullable($this->cacheCreationTokens, $other->cacheCreationTokens),
            cacheReadTokens: $this->addNullable($this->cacheReadTokens, $other->cacheReadTokens),
        );
    }

    private function addNullable(?int $a, ?int $b): ?int
    {
        return ($a === null && $b === null) ? null : (($a ?? 0) + ($b ?? 0));
    }
}
