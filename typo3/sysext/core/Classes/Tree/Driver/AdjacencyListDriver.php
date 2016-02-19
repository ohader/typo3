<?php
namespace TYPO3\CMS\Core\Tree\Driver;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Tree\Visitor\TreeNodeVisitorInterface;

class AdjacencyListDriver implements TreeDriverInterface
{
    /**
     * @var string
     */
    private $table = 'pages';

    /**
     * @var TreeNodeVisitorInterface
     */
    protected $visitor;

    /**
     * @var string
     */
    private $parentField = 'pid';

    /**
     * @var array
     */
    private $fieldArray = [
        'uid',
        'pid',
        'title'
    ];

    /**
     * @var array
     */
    private $tree = [];

    /**
     * @var array
     */
    private $data = [];

    /**
     * @var array
     */
    private $dataLookup = [];

    /**
     * @var array
     * @todo Take from backend user's UC
     */
    private $expandedNodes = [
        0 => [
            '2' => true,
            '1' => true,
        ]
    ];

    /**
     * @var int
     */
    private $mountIndex = 0;

    /**
     * @var string
     */
    private $clause;

    /**
     * @var int
     */
    private $subLevelID;

    /**
     * @var string
     */
    private $orderByFields = '';

    /**
     * @var int
     */
    private $expandAll = 1;

    /**
     * @param mixed $visitor
     */
    public function setVisitor($visitor)
    {
        $this->visitor = $visitor;
    }

    /**
     * @param string $identifier Starting identifier
     * @param int $depth Maximum nesting depth
     * @param bool $checkPermissions Whether to apply access permission checks
     * @return array
     */
    public function get($identifier, $depth = null, $checkPermissions = true)
    {
        $this->tree = [];
        $this->getTree($identifier);
        return $this->tree;
    }

    /**
     * Fetches the data for the tree
     *
     * @param int $uid item id for which to select subitems (parent id)
     * @param int $depth Max depth (recursivity limit)
     * @param int $depthData HTML-code prefix for recursive calls.

     * @return int The count of items on the level
     */
    private function getTree($uid, $depth = 999, $depthData = 0)
    {
        // Init vars
        $depth = (int)$depth;
        $res = $this->getDataInit($uid);
        $c = $this->getDataCount($res);
        $crazyRecursionLimiter = 999;
        // Traverse the records:
        while ($crazyRecursionLimiter > 0 && ($row = $this->getDataNext($res))) {
            $pageUid = ($this->table === 'pages') ? $row['uid'] : $row['pid'];
            if (!$this->getBackendUser()->isInWebMount($pageUid)) {
                // Current record is not within web mount => skip it
                continue;
            }

            $crazyRecursionLimiter--;
            $newID = $row['uid'];
            if ($newID == 0) {
                throw new \RuntimeException(
                    'Endless recursion detected: TYPO3 has detected an error in the database. Please fix it manually
                    (e.g. using phpMyAdmin) and change the UID of ' . $this->table . ':0 to a new value. See
                    http://forge.typo3.org/issues/16150 to get more information about a possible cause.',
                    1294586383
                );
            }
            // Reserve space.
            $this->tree[] = array();
            end($this->tree);
            // Get the key for this space
            $treeKey = key($this->tree);
            // Make a recursive call to the next level
            $hasSub = $this->expandNext($newID) && !$row['php_tree_stop'];
            if ($depth > 1 && $hasSub) {
                $nextCount = $this->getTree($newID, $depth - 1, $depthData + 1);
            } else {
                $nextCount = $this->getCount($newID);
            }

            // Finally, add the row/HTML content to the ->tree array in the reserved key.
            $nodeData = array(
                'row' => $row,
                'identifier' => $row['uid'], //@todo make configurable
                'mountIndex' => $this->mountIndex,
                'parent' => $uid,
                'depth' => $depthData,
                'label' => $row['title'], //@todo make configurable
                'expanded' => false, //@todo implement
                'hasChildren' => $nextCount && $hasSub,
                'icon' => '' //@todo implement
            );
            $nodeData = $this->visitor->enterNode($nodeData);
            $this->tree[$treeKey] = $nodeData;
        }

        $this->getDataFree($res);
        return $c;
    }

    /**
     * Getting the tree data: Selecting/Initializing data pointer to items for a certain parent id.
     * For tables: This will make a database query to select all children to "parent"
     * For arrays: This will return key to the ->dataLookup array
     *
     * @param int $parentId parent item id
     *
     * @return mixed Data handle (
     *                            Tables: An sql-resource
     *                            arrays: A parentId integer.
     *                            -1 is returned if there were NO subLevel.)
     * @access private
     */
    private function getDataInit($parentId)
    {
        if (is_array($this->data) && count($this->data) > 0) {
            if (!is_array($this->dataLookup[$parentId][$this->subLevelID])) {
                $parentId = -1;
            } else {
                reset($this->dataLookup[$parentId][$this->subLevelID]);
            }
            return $parentId;
        } else {
            $db = $this->getDatabaseConnection();
            $where = $this->parentField . '=' . $db->fullQuoteStr($parentId, $this->table)
                . BackendUtility::deleteClause($this->table)
                . BackendUtility::versioningPlaceholderClause($this->table)
                . $this->clause;
            return $db->exec_SELECTquery(
                implode(',', $this->fieldArray),
                $this->table,
                $where,
                '',
                $this->orderByFields
            );
        }
    }

    /**
     * Getting the tree data: Counting elements in resource
     *
     * @param mixed $res Data handle
     * @return int number of items
     * @access private
     * @see getDataInit()
     */
    private function getDataCount(&$res)
    {
        if (is_array($this->data) && count($this->data) > 0) {
            return count($this->dataLookup[$res][$this->subLevelID]);
        } else {
            return $this->getDatabaseConnection()->sql_num_rows($res);
        }
    }

    /**
     * Getting the tree data: next entry
     *
     * @param mixed $res Data handle
     *
     * @return array item data array OR FALSE if end of elements.
     * @access private
     * @see getDataInit()
     */
    private function getDataNext(&$res)
    {
        if (is_array($this->data) && count($this->data) > 0) {
            if ($res < 0) {
                $row = false;
            } else {
                list(, $row) = each($this->dataLookup[$res][$this->subLevelID]);
            }
            return $row;
        } else {
            while ($row = @$this->getDatabaseConnection()->sql_fetch_assoc($res)) {
                BackendUtility::workspaceOL(
                    $this->table,
                    $row,
                    $this->getBackendUser()->workspace,
                    true
                );
                if (is_array($row)) {
                    break;
                }
            }
            return $row;
        }
    }

    /**
     * Getting the tree data: frees data handle
     *
     * @param mixed $res Data handle
     * @return void
     * @access private
     */
    private function getDataFree(&$res)
    {
        if (!is_array($this->data)) {
            $this->getDatabaseConnection()->sql_free_result($res);
        }
    }

    /**
     * Returns TRUE/FALSE if the next level for $id should be expanded - based on
     * data in $this->stored[][] and ->expandAll flag.
     * Extending parent function
     *
     * @param int $id Record id/key
     * @return bool
     * @access private
     * @see \TYPO3\CMS\Backend\Tree\View\PageTreeView::expandNext()
     */
    public function expandNext($id)
    {
        $check = $this->expandedNodes[$this->mountIndex][$id] || $this->expandAll ? 1 : 0;

        return $check;
    }

    /**
     * Returns the number of records having the parent id, $uid
     *
     * @param int $uid Id to count subitems for
     * @return int
     * @access private
     */
    public function getCount($uid)
    {
        if (is_array($this->data)) {
            $res = $this->getDataInit($uid);
            return $this->getDataCount($res);
        } else {
            $db = $this->getDatabaseConnection();
            $where = $this->parentField . '='
                . $db->fullQuoteStr($uid, $this->table)
                . BackendUtility::deleteClause($this->table)
                . BackendUtility::versioningPlaceholderClause($this->table)
                . $this->clause;
            return $db->exec_SELECTcountRows('uid', $this->table, $where);
        }
    }

    /**
     * @return array
     */
    public function getRootNodes()
    {
        $mountPoints = (int)$this->getBackendUser()->uc['pageTree_temporaryMountPoint'];

        if (!$mountPoints) {
            $mountPoints = array_map('intval', $this->getBackendUser()->returnWebmounts());
            $mountPoints = array_unique($mountPoints);
        } else {
            $mountPoints = array($mountPoints);
        }

        if (empty($mountPoints)) {
            return [];
        }

        $rootNodes = [];
        foreach ($mountPoints as $mountPoint) {
            if ($mountPoint === 0) {
                $siteName = 'TYPO3';
                if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] !== '') {
                    $siteName = $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];
                }

                $record = array(
                    'uid' => 0,
                    'title' => $siteName
                );
                $rootNodes[] = $this->createRootNode($record);
            } else {
                $record = BackendUtility::getRecordWSOL($this->table, $mountPoint);

                if (empty($record)) {
                    continue;
                }

                $rootNodes[] = $this->createRootNode($record, count($rootNodes));
            }
        }

        return $rootNodes;
    }

    /**
     * @param string $parentIdentifier
     * @return int
     */
    private function countChildNodes($parentIdentifier)
    {
        $db = $this->getDatabaseConnection();
        $count = $db->exec_SELECTcountRows(
            'uid',
            $this->table,
            $this->parentField . '=' . $db->fullQuoteStr($parentIdentifier, $this->table)
        );
        return (int)$count;
    }

    /**
     * @param array $record
     * @param int $mountIndex
     * @return array
     */
    private function createRootNode(array $record, $mountIndex = 0)
    {
        $labelField = 'title';
        if (!empty($GLOBALS['TCA'][$this->table]['ctrl']['label'])) {
            $labelField = $GLOBALS['TCA'][$this->table]['ctrl']['label'];
        }

        $hasChildren = (bool)$this->countChildNodes($record['uid']);

        return [
            'identifier' => $record['uid'],
            'mountIndex' => $mountIndex,
            'parent' => null,
            'depth' => 0,
            'label' => $record[$labelField],
            'expanded' => false, //@todo implement
            'hasChildren' => $hasChildren,
            'icon' => '' //@todo implement
        ];
    }

    /**
     * @return BackendUserAuthentication
     */
    private function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @return DatabaseConnection
     */
    private function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }

    /**
     * @param int      $identifier
     * @param int|null $depth
     * @param bool     $checkPermissions
     *
     * @return mixed
     */
    public function getChildren($identifier, $depth = null, $checkPermissions = true)
    {
        // TODO: Implement getChildren() method.
    }

    /**
     * @param int $identifier
     *
     * @return int
     */
    public function getDepth($identifier)
    {
        // TODO: Implement getDepth() method.
}}
