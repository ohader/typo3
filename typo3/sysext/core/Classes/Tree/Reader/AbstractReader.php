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

/**
 * Class AbstractReader
 * @package TYPO3\CMS\Core\Tree\Reader
 */
abstract class AbstractReader implements ReaderInterface
{
    /**
     * Gets flat array of tree node elements.
     *
     * @param string $identifier Starting identifier
     * @param int $depth Maximum nesting depth
     * @param bool $checkPermissions Whether to apply access permission checks
     * @return array
     */
    public function get($identifier, $depth = null, $checkPermissions = true)
    {
        if ($identifier === static::IDENTIFIER_Root) {
            $nodes = [];
            $rootNodes = $this->getRootNodes();
            foreach ($rootNodes as $rootNode) {
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
}
