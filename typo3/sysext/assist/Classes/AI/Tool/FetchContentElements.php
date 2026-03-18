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

namespace TYPO3\CMS\Assist\AI\Tool;

use Doctrine\DBAL\ParameterType;
use Symfony\AI\Agent\Toolbox\Attribute\AsTool;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Assist\Service\BackendUserPermissionService;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;

#[Autoconfigure(public: true)]
#[AsTool(
    name: 'typo3-assist-fetchContentElements',
    description: 'Fetch content elements (tt_content) for a given page UID. Returns headers and body text.',
)]
final readonly class FetchContentElements
{
    private const FIELDS = ['uid', 'pid', 'CType', 'header', 'subheader', 'bodytext'];

    public function __construct(
        private BackendUserPermissionService $permissionService,
        private ConnectionPool $connectionPool,
    ) {}

    /**
     * @param int $pageUid The UID of the page whose content elements to fetch
     * @param bool $showHidden Whether to include hidden content elements in the result
     * @param int|null $limit Maximum number of records to return
     * @return array<int, array<string, mixed>>
     */
    public function __invoke(int $pageUid, bool $showHidden = false, ?int $limit = null): array
    {
        if (!$this->permissionService->isInWebMount($pageUid)) {
            return [];
        }

        $allowedFields = array_filter(
            self::FIELDS,
            fn(string $fieldName) => $this->permissionService->canAccessTableField('tt_content', $fieldName)
        );

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tt_content');
        if ($showHidden) {
            $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);
        }

        $result = $queryBuilder->select(...$allowedFields)
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter($pageUid, ParameterType::INTEGER)
                )
            )
            ->executeQuery();

        $records = $result->fetchAllAssociative();

        foreach ($records as &$record) {
            if (isset($record['bodytext'])) {
                $record['bodytext'] = strip_tags((string)$record['bodytext']);
            }
        }
        unset($record);

        if ($limit !== null) {
            $records = array_slice($records, 0, $limit);
        }

        return array_values($records);
    }
}
