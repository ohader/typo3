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

/**
 * Class PageNodeVisitor
 *
 * @package TYPO3\CMS\Core\Tree\Visitor
 */
class PageNodeVisitor implements NodeVisitorInterface
{
    /**
     * @param array $nodes
     *
     * @return mixed
     */
    public function beforeTraverse(array $nodes)
    {
        // TODO: Implement beforeTraverse() method.
    }

    /**
     * @param array $node
     *
     * @return mixed
     */
    public function enterNode(array $node)
    {
        // Fiddle around
        return $node;
    }

    /**
     * @param array $node
     *
     * @return mixed
     */
    public function leaveNode(array $node)
    {
        $identifier = dechex($node['identifier']);
        if (!empty($this->getBackendUser()->uc['BackendComponents']['States']['Pagetree']->stateHash->{$identifier})) {
            $node['expanded'] = true;
        }

        // Purge database row from node data
        unset($node['row']);
        return $node;
    }

    /**
     * @param array $nodes
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
