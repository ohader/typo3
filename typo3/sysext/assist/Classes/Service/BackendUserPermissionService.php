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

namespace TYPO3\CMS\Assist\Service;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;

final readonly class BackendUserPermissionService
{
    public function __construct(
        private TcaSchemaFactory $schemaFactory,
    ) {}

    public function isInWebMount(int $pageId): bool
    {
        return $this->getBackendUser()->isInWebMount($pageId) !== null;
    }

    public function canReadFromTable(string $tableName): bool
    {
        return $this->getBackendUser()->check('tables_select', $tableName);
    }

    public function canAccessTableField(string $tableName, string $fieldName): bool
    {
        if (!$this->schemaFactory->has($tableName)) {
            return false;
        }
        $schema = $this->schemaFactory->get($tableName);
        return !(
            $schema->hasField($fieldName)
            && $schema->getField($fieldName)->supportsAccessControl()
            && !$this->getBackendUser()->check('non_exclude_fields', $tableName . ':' . $fieldName)
        );
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
