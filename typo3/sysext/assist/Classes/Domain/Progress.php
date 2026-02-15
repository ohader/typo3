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

namespace TYPO3\CMS\Assist\Domain;

use Symfony\Component\Uid\Uuid;

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
        public string $model,
        public array $items,
    ) {
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    public static function fromRows(array $rows): self
    {
        usort($rows, static fn(array $a, array $b): int => $a['sequence'] <=> $b['sequence']);

        $firstRow = $rows[0];
        return new self(
            uuid: Uuid::fromString($firstRow['uuid']),
            model: (string)$firstRow['model'],
            items: array_map(ProgressItem::fromRow(...), $rows),
        );
    }
}
