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

namespace TYPO3\CMS\Assist\Domain\Model;

use Symfony\Component\Uid\Uuid;
use TYPO3\CMS\Assist\AI\Platform\PlatformModelFactory;
use TYPO3\CMS\Assist\Domain\Enum\ProgressItemType;

/**
 * @internal
 */
final readonly class Progress
{
    /**
     * @param list<ProgressItem> $items
     */
    public function __construct(
        public Uuid $uuid,
        public PlatformModel $model,
        public Initiator $initiator,
        public int $userId,
        public array $items = [],
        public ?StepCollection $steps = null,
        public ?StateCollection $state = null,
    ) {}

    /**
     * @param array<string, mixed> $parentRow
     * @param list<array<string, mixed>> $itemRows
     */
    public static function fromRows(array $parentRow, array $itemRows, PlatformModelFactory $factory): self
    {
        usort($itemRows, static fn(array $a, array $b): int => $a['sequence'] <=> $b['sequence']);

        $stepsRow = null;
        $stateRow = null;
        foreach ($itemRows as $row) {
            // only takes the last occurrence here
            $rowType = ProgressItemType::tryFrom($row['type']);
            if ($rowType === ProgressItemType::steps) {
                $stepsRow = $row;
            }
            if ($rowType === ProgressItemType::state) {
                $stateRow = $row;
            }
        }
        $steps = $stepsRow !== null
            ? StepCollection::fromArray(json_decode($stepsRow['payload'], true))
            : null;
        $state = $stateRow !== null
            ? StateCollection::fromArray(json_decode($stateRow['payload'], true))
            : null;

        return new self(
            uuid: Uuid::fromString($parentRow['uuid']),
            model: $factory->fromString($parentRow['model']),
            initiator: Initiator::fromJson($parentRow['initiator']),
            userId: (int)$parentRow['user_id'],
            items: array_map(ProgressItem::fromRow(...), $itemRows),
            steps: $steps,
            state: $state,
        );
    }
}
