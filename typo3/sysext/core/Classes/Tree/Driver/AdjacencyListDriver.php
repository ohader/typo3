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
use TYPO3\CMS\Core\Tree\Model\Node;
use TYPO3\CMS\Core\Tree\Visitor\NodeVisitorInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class AdjacencyListDriver implements DriverInterface
{
    /**
     * @var string
     */
    protected $tableName;

    /**
     * @var string
     */
    protected $parentFieldName;

    /**
     * @var string
     */
    protected $labelFieldName;

    /**
     * @var NodeVisitorInterface
     */
    protected $visitor;


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
     * @var Node[]
     */
    protected $rootNodes;

    /**
     * @var int[]
     */
    protected $rootNodeIds = [];

    /**
     * @var string
     */
    protected $computedFieldList;

    /**
     * @param string $tableName
     * @param string $parentFieldName
     * @param string $labelFieldName
     */
    public function __construct($tableName, $parentFieldName, $labelFieldName)
    {
        $this->setTableName($tableName);
        $this->setParentFieldName($parentFieldName);
        $this->setLabelFieldName($labelFieldName);
    }

    /**
     * @param mixed $visitor
     */
    public function setVisitor($visitor)
    {
        $this->visitor = $visitor;
    }

    /**
     * @param string $tableName
     */
    public function setTableName($tableName)
    {
        $this->tableName = $tableName;
    }

    /**
     * @param string $parentFieldName
     */
    public function setParentFieldName($parentFieldName)
    {
        $this->parentFieldName = $parentFieldName;
        $this->computeFieldList();
    }

    /**
     * @param string $labelFieldName
     */
    public function setLabelFieldName($labelFieldName)
    {
        $this->labelFieldName = $labelFieldName;
        $this->computeFieldList();
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
        $currentDepth = $this->getDepth($identifier);
        $this->getTree($identifier, $depth, $currentDepth + 1);
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
        if ($depth === null) {
            $depth = 999;
        }

        // Init vars
        $depth = (int)$depth;
        $res = $this->getDataInit($uid);
        $c = $this->getDataCount($res);
        $crazyRecursionLimiter = 999;
        // Traverse the records:
        while ($crazyRecursionLimiter > 0 && ($row = $this->getDataNext($res))) {
            /** @var Node $node */
            $node = GeneralUtility::makeInstance(Node::class);

            $node->internalData = $row;
            $node->mountIndex = $this->mountIndex;
            $node->identifier = $row['uid'];
            $node->parent = $row[$this->parentFieldName];
            $node->depth = $depthData;
            $node->label = $row[$this->labelFieldName];

            $node = $this->visitor->enterNode($node);
            if ($node === NodeVisitorInterface::COMMAND_SKIP) {
                continue;
            }

            $pageUid = ($this->tableName === 'pages') ? $row['uid'] : $row['pid'];
            if (!$this->getBackendUser()->isInWebMount($pageUid)) {
                // Current record is not within web mount => skip it
                continue;
            }

            $crazyRecursionLimiter--;
            $newID = $row['uid'];
            if ($newID == 0) {
                throw new \RuntimeException(
                    'Endless recursion detected: TYPO3 has detected an error in the database. Please fix it manually
                    (e.g. using phpMyAdmin) and change the UID of ' . $this->tableName . ':0 to a new value. See
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

            $node->expanded = false;
            $node->hasChildren = ($nextCount && $hasSub);
            $node->icon = '';

            $node = $this->visitor->leaveNode($node);
            if ($node === NodeVisitorInterface::COMMAND_SKIP) {
                continue;
            }

            $this->tree[$treeKey] = $node->__toArray();
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
            $where = $this->parentFieldName . '=' . $db->fullQuoteStr($parentId, $this->tableName)
                . BackendUtility::deleteClause($this->tableName)
                . BackendUtility::versioningPlaceholderClause($this->tableName)
                . $this->clause;
            return $db->exec_SELECTquery(
                $this->computedFieldList,
                $this->tableName,
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
                    $this->tableName,
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
        // @todo Move to visitor
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
            $where = $this->parentFieldName . '='
                . $db->fullQuoteStr($uid, $this->tableName)
                . BackendUtility::deleteClause($this->tableName)
                . BackendUtility::versioningPlaceholderClause($this->tableName)
                . $this->clause;
            return $db->exec_SELECTcountRows('uid', $this->tableName, $where);
        }
    }

    /**
     * @return Node[]
     */
    public function getRootNodes()
    {
        if (isset($this->rootNodes)) {
            return $this->rootNodes;
        }

        $this->rootNodes = [];

        $mountPoints = (int)$this->getBackendUser()->uc['pageTree_temporaryMountPoint'];

        if (!$mountPoints) {
            $mountPoints = array_map('intval', $this->getBackendUser()->returnWebmounts());
            $mountPoints = array_unique($mountPoints);
        } else {
            $mountPoints = array($mountPoints);
        }

        if (empty($mountPoints)) {
            return $this->rootNodes;
        }

        $rootNodes = [];
        $this->visitor->beforeTraverse($rootNodes);

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
            } else {
                $record = BackendUtility::getRecordWSOL($this->tableName, $mountPoint, $this->computedFieldList);

                if (empty($record)) {
                    continue;
                }
            }

            /** @var Node $node */
            $rootNode = GeneralUtility::makeInstance(Node::class);
            $rootNode->internalData = $record;
            $rootNode->mountIndex = count($rootNodes);
            $rootNode->identifier = $record['uid'];
            $rootNode->depth = 0;

            $result = $this->visitor->enterNode($rootNode);
            if ($result === NodeVisitorInterface::COMMAND_SKIP) {
                continue;
            }

            $rootNode->label = $record[$this->labelFieldName];
            $rootNode->expanded = false; //@todo implement
            $rootNode->icon = ''; //@todo implement
            $rootNode->hasChildren = (bool)$this->countChildNodes($record['uid']);

            $result = $this->visitor->leaveNode($rootNode);
            if ($result === NodeVisitorInterface::COMMAND_SKIP) {
                continue;
            }

            $this->rootNodeIds[] = $mountPoint;
            $rootNodes[] = $rootNode;
        }

        $this->visitor->afterTraverse($rootNodes);

        $this->rootNodes = array_map(array($this, 'nodeToArray'), $rootNodes);
        return $this->rootNodes;
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
            $this->tableName,
            $this->parentFieldName . '=' . $db->fullQuoteStr($parentIdentifier, $this->tableName)
        );
        return (int)$count;
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
        $this->getRootNodes();
        $rootLineIds = null;
        $rootLine = array_reverse(BackendUtility::BEgetRootLine($identifier, '', true));
        foreach ($rootLine as $page) {
            if (in_array($page['uid'], $this->rootNodeIds)) {
                $rootLineIds = [];
            } elseif ($rootLineIds !== null) {
                $rootLineIds[] = $page['uid'];
            }
        }
        return count($rootLineIds);
    }

    /**
     * @return void
     */
    protected function computeFieldList()
    {
        $fieldNames = array(
            'uid',
            $this->parentFieldName,
            $this->labelFieldName,
        );
        $this->computedFieldList = implode(',', $fieldNames);
    }

    protected function nodeToArray(Node $node) {
        return $node->__toArray();
    }
}
