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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class PageReader
 * @package TYPO3\CMS\Core\Tree\Reader
 */
class PageReader extends AbstractReader
{
    /**
     * @var AdjacencyListTreeReader
     */
    protected $adjacencyListTreeReader;

    /**
     * PageReader constructor.
     */
    public function __construct()
    {
        $this->adjacencyListTreeReader = GeneralUtility::makeInstance(AdjacencyListTreeReader::class);
    }

    /**
     * @param string $identifier
     * @param null $depth
     * @param bool $checkPermissions
     * @return array
     */
    public function get($identifier, $depth = null, $checkPermissions = true)
    {
        if ($identifier === static::IDENTIFIER_Root) {
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
        return $this->adjacencyListTreeReader->getRootNodes();
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
        return $this->adjacencyListTreeReader->get($identifier, $depth, $checkPermissions);
    }

    /**
     * @param int $identifier
     * @return int
     */
    public function getDepth($identifier)
    {
        // TODO: Implement getDepth() method.
    }
}
