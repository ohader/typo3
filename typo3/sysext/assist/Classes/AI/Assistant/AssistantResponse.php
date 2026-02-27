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

namespace TYPO3\CMS\Assist\AI\Assistant;

use Symfony\AI\Platform\Result\ResultInterface;
use TYPO3\CMS\Assist\AI\Assistant\Feedback\FeedbackInterface;
use TYPO3\CMS\Assist\Domain\Model\Progress;
use TYPO3\CMS\Assist\Domain\Model\Step;
use TYPO3\CMS\Core\Http\JsonResponse;

final readonly class AssistantResponse
{
    /**
     * @param list<ResultInterface|FeedbackInterface> $results
     * @param list<Step> $steps
     */
    public function __construct(
        public array $results = [],
        public array $steps = [],
        public ?Progress $progress = null,
    ) {}

    public function toResponse(): JsonResponse
    {
        return new JsonResponse([
            'results' => array_map(
                static fn(ResultInterface|FeedbackInterface $item): mixed
                    => $item instanceof FeedbackInterface ? $item : $item->getContent(),
                $this->results,
            ),
            'steps' => $this->steps,
            'progress' => $this->progress !== null
                ? ['uuid' => (string)$this->progress->uuid]
                : null,
        ]);
    }
}
