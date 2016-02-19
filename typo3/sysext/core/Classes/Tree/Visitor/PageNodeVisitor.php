<?php
namespace TYPO3\CMS\Core\Tree\Visitor;

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

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Tree\Model\Node;

/**
 * Class PageNodeVisitor
 *
 * @package TYPO3\CMS\Core\Tree\Visitor
 */
class PageNodeVisitor implements NodeVisitorInterface
{
    /**
     * @param Node[] $nodes
     *
     * @return mixed
     */
    public function beforeTraverse(array $nodes)
    {
        // TODO: Implement beforeTraverse() method.
    }

    /**
     * @param Node $node
     *
     * @return mixed
     */
    public function enterNode(Node $node)
    {
        // Fiddle around
        return $node;
    }

    /**
     * @param Node $node
     *
     * @return mixed
     */
    public function leaveNode(Node $node)
    {
        $identifier = dechex($node->identifier);
        if (!empty($this->getBackendUser()->uc['BackendComponents']['States']['Pagetree']->stateHash->{$identifier})) {
            $node->expanded = true;
        }

        // Unset internal data of node
        unset($node->internalData);

        return $node;
    }

    /**
     * @param Node[] $nodes
     *
     * @return mixed
     */
    public function afterTraverse(array $nodes)
    {
        // TODO: Implement afterTraverse() method.
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
