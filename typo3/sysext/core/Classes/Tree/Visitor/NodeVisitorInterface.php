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

interface NodeVisitorInterface
{
    /**
     * The beforeTraverse() method is called once before the traversal begins and is passed the nodes the traverser was
     * called with. This method can be used for resetting values before traversation or preparing the tree for
     * traversal.
     *
     * @param array $nodes
     *
     * @return mixed
     */
    public function beforeTraverse(array $nodes);

    /**
     * The enterNode() and leaveNode() methods are called on every node, the former when it is entered, i.e. before its
     * subnodes are traversed, the latter when it is left.
     *
     * @param array $node
     *
     * @return mixed
     */
    public function enterNode(array $node);

    /**
     * @param array $node
     *
     * @return mixed
     */
    public function leaveNode(array $node);

    /**
     * The afterTraverse() method is similar to the beforeTraverse() method, with the only difference that it is called
     * once after the traversal.
     *
     * @param array $nodes
     *
     * @return mixed
     */
    public function afterTraverse(array $nodes);
}
