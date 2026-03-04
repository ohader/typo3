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

namespace TYPO3\CMS\Assist\Domain\Repository;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Uid\Uuid;
use TYPO3\CMS\Assist\Domain\Model\Progress;
use TYPO3\CMS\Assist\Domain\Model\ProgressItem;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;

/**
 * @internal
 * @todo drop Autoconfigure, once the repository is actually used via DI in other components
 */
#[Autoconfigure(public: true)]
final readonly class ProgressRepository
{
    private const TABLE_NAME = 'sys_assist_progress';
    private const ITEMS_TABLE = 'sys_assist_progress_item';

    public function __construct(private ConnectionPool $pool) {}

    public function findByUuid(Uuid $uuid): ?Progress
    {
        $queryBuilder = $this->getQueryBuilder();
        $parentRow = $queryBuilder
            ->select('*')
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq(
                    'uuid',
                    $queryBuilder->createNamedParameter((string)$uuid)
                )
            )
            ->executeQuery()
            ->fetchAssociative();

        if ($parentRow === false) {
            return null;
        }

        $itemsQueryBuilder = $this->getItemsQueryBuilder();
        $itemRows = $itemsQueryBuilder
            ->select('*')
            ->from(self::ITEMS_TABLE)
            ->where(
                $itemsQueryBuilder->expr()->eq(
                    'progress',
                    $itemsQueryBuilder->createNamedParameter((string)$uuid)
                )
            )
            ->executeQuery()
            ->fetchAllAssociative();

        return Progress::fromRows($parentRow, $itemRows);
    }

    /**
     * @todo does not check whether sequence exists already -> would fail on DB side
     */
    public function add(Progress $progress): void
    {
        $this->getConnection()->insert(
            self::TABLE_NAME,
            [
                'uuid' => (string)$progress->uuid,
                'model' => (string)$progress->model,
                'initiator' => json_encode($progress->initiator->toArray()),
                'user_id' => $progress->userId,
                'timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s.v'),
            ]
        );
        foreach ($progress->items as $sequence => $item) {
            $this->assign($progress->uuid, $sequence, $item);
        }
    }

    public function append(Uuid $uuid, ProgressItem $item): int
    {
        $sequence = $this->nextSequence($uuid);
        $this->assign($uuid, $sequence, $item);
        return $sequence;
    }

    private function assign(Uuid $uuid, int $sequence, ProgressItem $item): void
    {
        $this->getItemsConnection()->insert(
            self::ITEMS_TABLE,
            [
                'uuid' => (string)$item->uuid,
                'progress' => (string)$uuid,
                'sequence' => $sequence,
                'type' => $item->type->value,
                'timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s.v'),
                'payload' => json_encode($item->payload),
            ]
        );
    }

    private function nextSequence(Uuid $uuid): int
    {
        $queryBuilder = $this->getItemsQueryBuilder();
        $result = $queryBuilder
            ->selectLiteral('MAX(' . $queryBuilder->quoteIdentifier('sequence') . ') AS ' . $queryBuilder->quoteIdentifier('max_sequence'))
            ->from(self::ITEMS_TABLE)
            ->where(
                $queryBuilder->expr()->eq(
                    'progress',
                    $queryBuilder->createNamedParameter((string)$uuid)
                )
            )
            ->executeQuery();
        $row = $result->fetchAssociative();
        if ($row === false || $row['max_sequence'] === null) {
            return 0;
        }
        return (int)$row['max_sequence'] + 1;
    }

    private function getQueryBuilder(): QueryBuilder
    {
        return $this->pool->getQueryBuilderForTable(self::TABLE_NAME);
    }

    private function getConnection(): Connection
    {
        return $this->pool->getConnectionForTable(self::TABLE_NAME);
    }

    private function getItemsQueryBuilder(): QueryBuilder
    {
        return $this->pool->getQueryBuilderForTable(self::ITEMS_TABLE);
    }

    private function getItemsConnection(): Connection
    {
        return $this->pool->getConnectionForTable(self::ITEMS_TABLE);
    }
}
