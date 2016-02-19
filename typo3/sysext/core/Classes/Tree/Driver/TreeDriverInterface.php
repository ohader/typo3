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

/**
 * Interface ReaderInterface
 * @package TYPO3\CMS\Core\Tree\Reader
 */
interface TreeDriverInterface
{
    const IDENTIFIER_ROOT = 'root';

    /**
     * Gets flat array of tree node elements.
     *
     * @param string $identifier Starting identifier
     * @param int $depth Maximum nesting depth
     * @param bool $checkPermissions Whether to apply access permission checks
     * @return array
     */
    public function get($identifier, $depth = null, $checkPermissions = true);

    /**
     * @return array
     */
    public function getRootNodes();

    /**
     * @param int $identifier
     * @param int|null $depth
     * @param bool $checkPermissions
     * @return mixed
     */
    public function getChildren($identifier, $depth = null, $checkPermissions = true);

    /**
     * @param int $identifier
     * @return int
     */
    public function getDepth($identifier);
}
