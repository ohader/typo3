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

/**
 * Class AbstractNodeVisitor
 */
abstract class AbstractNodeVisitor
{
    /**
     * @var bool
     */
    protected $expandAll = false;

    /**
     * @param bool $expandAll
     */
    public function setExpandAll($expandAll)
    {
        $this->expandAll = (bool)$expandAll;
    }

    /**
     * @return bool
     */
    public function getExpandAll()
    {
        return $this->expandAll;
    }
}
