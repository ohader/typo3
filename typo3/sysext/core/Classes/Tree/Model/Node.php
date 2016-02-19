<?php
namespace TYPO3\CMS\Core\Tree\Model;

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
 * Class Node
 */
class Node
{
    /**
     * @var mixed
     * @internal
     */
    public $internalData;

    /**
     * @var int
     */
    public $mountIndex;

    /**
     * @var string
     */
    public $identifier;

    /**
     * @var string
     */
    public $parent;

    /**
     * @var int
     */
    public $depth;

    /**
     * @var string
     */
    public $icon;

    /**
     * @var string
     */
    public $label;

    /**
     * @var bool
     */
    public $expanded = false;

    /**
     * @var bool
     */
    public $hasChildren = false;

    /**
     * @var array
     */
    public $attributes = [];

    /**
     * @var array
     */
    public $styles = [];

    /**
     * Gets array representation of object.
     *
     * @return array
     */
    public function __toArray()
    {
        return [
            'mountIndex' => $this->mountIndex,
            'identifier' => $this->identifier,
            'parent' => $this->parent,
            'depth' => $this->depth,
            'icon' => $this->icon,
            'label' => $this->label,
            'expanded' => $this->expanded,
            'hasChildren' => $this->hasChildren,

            // @todo Activate and add to functional tests
            // 'attributes' => $this->attributes,
            // 'styles' => $this->styles,
        ];
    }
}
