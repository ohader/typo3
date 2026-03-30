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

final readonly class ModelOptions
{
    /**
     * @param float $temperature controls creativity of the model (0.0 picks the single most likely next token, above 1.0 things get chaotic)
     * @param int|null $maxTokens total amount of tokens the model is allowed to consume (512-1024 for chat assistants) to avoid runaway loops (upper bound is the model's context window)
     * @param float|null $repetitionPenalty penalty to the model when repeating used tokens (1.0 mean no penalty, above 1.3 models use strange vocabulary)
     */
    public function __construct(
        public float $temperature = 0.5,
        public ?int $maxTokens = null,
        public ?float $repetitionPenalty = 1.0,
    ) {}
}
