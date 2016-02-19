<?php
namespace TYPO3\CMS\Core\Tree\Reader;

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

use TYPO3\CMS\Core\Tree\Driver\AdjacencyListDriver;
use TYPO3\CMS\Core\Tree\Driver\DriverInterface;
use TYPO3\CMS\Core\Tree\Visitor\PageNodeVisitor;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class PageReader
 *
 * @package TYPO3\CMS\Core\Tree\Driver
 */
class PageReader implements ReaderInterface
{
    /**
     * @var AdjacencyListDriver
     */
    protected $driver;

    /**
     * PageReader constructor.
     */
    public function __construct()
    {
        $this->driver = GeneralUtility::makeInstance(
            AdjacencyListDriver::class,
            'pages',
            'pid',
            'title'
        );
        $this->driver->setVisitor(GeneralUtility::makeInstance(PageNodeVisitor::class));
    }

    public function setExpandAll($expandAll)
    {
        $this->driver->getVisitor()->setExpandAll($expandAll);
    }

    /**
     * Gets flat array of tree node elements.
     *
     * @param string $identifier
     * @param null $depth
     * @param bool $checkPermissions
     * @return array
     */
    public function get($identifier, $depth = null, $checkPermissions = true)
    {
        if ($identifier === DriverInterface::IDENTIFIER_ROOT) {
            $nodes = [];
            $rootNodes = $this->getRootNodes();
            foreach ($rootNodes as $rootNode) {
                $nodes[] = $rootNode;
                $nodes = array_merge(
                    $nodes,
                    $this->getChildren($rootNode['identifier'], $depth, $checkPermissions)
                );
            }
        } else {
            $nodes = $this->getChildren($identifier, $depth, $checkPermissions);
        }

        return $nodes;
    }


    /**
     * @return array
     */
    public function getRootNodes()
    {
        return $this->driver->getRootNodes();
    }

    /**
     * @param int $identifier
     * @param null $depth
     * @param bool $checkPermissions
     * @return array
     */
    public function getChildren($identifier, $depth = null, $checkPermissions = true)
    {
        // @todo $depth and $checkPermissions are currently not supported
        return $this->driver->get($identifier, $depth, $checkPermissions);
    }

    /**
     * @param int $identifier
     * @return int
     */
    public function getDepth($identifier)
    {
        return $this->driver->getDepth($identifier);
    }
}
