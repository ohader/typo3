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

use Symfony\AI\Agent\Toolbox\Attribute\AsTool;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Assist\Service\BackendUserPermissionService;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;

#[Autoconfigure(public: true)]
#[AsTool(
    name: 'typo3-assist-fetchPages',
    description: 'Fetch pages records from the database table `pages`. ' .
        'The result is best visualized in a markdown table if not request in a different format.'
)]
final readonly class FetchPageRecordsTool
{
    private const FIELDS = ['uid', 'pid', 'title', 'subtitle', 'keywords', 'description', 'abstract'];

    public function __construct(
        private BackendUserPermissionService $permissionService,
        private ConnectionPool $connectionPool,
    ) {}

    /**
     * @param string $filter Search filter applied to pages title
     * @param bool $showHidden Whether to include hidden pages in the result
     * @param int|null $limit Maximum number of pages to return
     * @return array<int, array{uid: int, title: string, pid: int, tstamp: int, hidden: bool}>
     */
    public function __invoke(
        string $filter = '',
        bool $showHidden = false,
        ?int $limit = null,
    ): array {
        $predicates = [];
        $allowedFields = array_filter(
            self::FIELDS,
            fn(string $fieldName) => $this->permissionService->canAccessTableField('pages', $fieldName)
        );
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        if ($showHidden) {
            $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);
        }
        if ($filter !== '') {
            $predicates[] = $queryBuilder->expr()->like(
                'title',
                $queryBuilder->createNamedParameter('%' . $queryBuilder->escapeLikeWildcards($filter) . '%')
            );
        }
        $result = $queryBuilder->select(...$allowedFields)
            ->from('pages')
            ->where(...$predicates)
            ->executeQuery();
        $pages = array_values(array_filter(
            $result->fetchAllAssociative(),
            fn(array $page) => $this->permissionService->isInWebMount($page['uid'] ?? 0)
        ));
        if ($limit !== null) {
            $pages = array_slice($pages, 0, $limit);
        }
        return $pages;
    }
}
