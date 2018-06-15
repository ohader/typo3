<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Tests\Unit\Configuration\MetaModel;

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

use TYPO3\CMS\Core\Configuration\MetaModel\EntityRelationMapFactory;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class EntityRelationMapFactoryTest extends UnitTestCase
{
    /**
     * @var array
     */
    protected $configuration;

    /**
     * @var array
     */
    protected $expected;

    protected function setUp()
    {
        parent::setUp();
        $this->configuration = [
            'sys_language' => [
                'columns' => [
                    'title' => [
                        'config' => [
                            'type' => 'input',
                        ],
                    ],
                ],
            ],
            'content' => [
                'columns' => [
                    'language' => [
                        'config' => [
                            'type' => 'select',
                            'special' => 'languages',
                        ],
                    ],
                    'layout' => [
                        'config' => [
                            'type' => 'select',
                            'foreign_table' => 'layout',
                        ],
                    ],
                    'text' => [
                        'config' => [
                            'type' => 'input',
                        ],
                    ],
                    'imageReference' => [
                        'config' => [
                            'type' => 'inline',
                            'foreign_table' => 'image',
                        ],
                    ],
                    'imageComposition' => [
                        'config' => [
                            'type' => 'inline',
                            'foreign_table' => 'image',
                            'foreign_field' => 'content',
                        ],
                    ],
                    'categories' => [
                        'config' => [
                            'type' => 'group',
                            'internal_type' => 'db',
                            'allowed' => 'category',
                            'MM' => 'categories_mm',
                            'MM_opposite_field' => 'items',
                        ],
                    ],
                    'entities' => [
                        'config' => [
                            'type' => 'group',
                            'internal_type' => 'db',
                            'allowed' => '*',
                        ],
                    ],
                    'related' => [
                        'config' => [
                            'type' => 'group',
                            'internal_type' => 'db',
                            'allowed' => 'content,image',
                        ],
                    ]
                ]
            ],
            'image' => [
                'columns' => [
                    'language' => [
                        'config' => [
                            'type' => 'select',
                            'special' => 'languages',
                        ],
                    ],
                    'url' => [
                        'config' => [
                            'type' => 'input',
                        ],
                    ],
                ],
            ],
            'layout' => [
                'columns' => [
                    'identifier' => [
                        'config' => [
                            'type' => 'input',
                        ],
                    ],
                ],
            ],
            'category' => [
                'columns' => [
                    'title' => [
                        'config' => [
                            'type' => 'input',
                        ],
                    ],
                    'items' => [
                        'config' => [
                            'type' => 'group',
                            'internal_type' => 'db',
                            'allowed' => '*',
                            'MM' => 'categories_mm',
                            'MM_oppositeUsage' => [
                                'content' => [
                                    'categories',
                                ],
                            ],
                        ],
                    ]
                ],
            ],
        ];

        $this->expected = [
            'default' => [
                'content' => [
                    'language' => [
                        'active' => [
                            'content.language -> sys_language',
                        ],
                    ],
                    'layout' => [
                        'active' => [
                            'content.layout -> layout',
                        ],
                    ],
                    'imageReference' => [
                        'active' => [
                            'content.imageReference -> image',
                        ],
                    ],
                    'imageComposition' => [
                        'active' => [
                            'content.imageComposition -> image.content',
                        ],
                    ],
                    'categories' => [
                        'active' => [
                            'content.categories -> category',
                        ],
                    ],
                    'entities' => [
                        'active' => [
                            'content.entities -> sys_language',
                            'content.entities -> content',
                            'content.entities -> image',
                            'content.entities -> layout',
                            'content.entities -> category',
                        ],
                    ],
                    'related' => [
                        'active' => [
                            'content.related -> content',
                            'content.related -> image',
                        ],
                    ],
                ],
                'image' => [
                    'language' => [
                        'active' => [
                            'image.language -> sys_language',
                        ],
                    ],
                    'content' => [
                        'passive' => [
                            'image.content <- content.imageComposition',
                        ],
                    ],
                ],
                'category' => [
                    'items' => [
                        'active' => [
                            'category.items -> sys_language',
                            'category.items -> content',
                            'category.items -> image',
                            'category.items -> layout',
                            'category.items -> category',
                        ],
                    ]
                ]
            ],
            'identity' => [
                'sys_language' => [
                    '__identity' => [
                        'passive' => [
                            'sys_language.__identity <- content.language',
                            'sys_language.__identity <- content.entities',
                            'sys_language.__identity <- image.language',
                            'sys_language.__identity <- category.items',
                        ],
                    ],
                ],
                'content' => [
                    '__identity' => [
                        'passive' => [
                            'content.__identity <- content.entities',
                            'content.__identity <- content.related',
                            'content.__identity <- category.items',
                        ],
                    ],
                ],
                'image' => [
                    '__identity' => [
                        'passive' => [
                            'image.__identity <- content.imageReference',
                            'image.__identity <- content.entities',
                            'image.__identity <- content.related',
                            'image.__identity <- category.items',
                        ],
                    ],
                ],
                'layout' => [
                    '__identity' => [
                        'passive' => [
                            'layout.__identity <- content.layout',
                            'layout.__identity <- content.entities',
                            'layout.__identity <- category.items',
                        ],
                    ],
                ],
                'category' => [
                    '__identity' => [
                        'passive' => [
                            'category.__identity <- content.categories',
                            'category.__identity <- content.entities',
                            'category.__identity <- category.items',
                        ],
                    ],
                ],
            ],
            'opposite' => [
                'content' => [
                    'categories' => [
                        'active' => [
                            'content.categories -> category.items',
                        ],
                    ],
                ],
                'category' => [
                    'items' => [
                        'passive' => [
                            'category.items <- content.categories'
                        ],
                    ],
                ],
            ]
        ];
    }

    /**
     * @test
     */
    public function defaultRelationsAreResolved()
    {
        $factory = new EntityRelationMapFactory(
            $this->configuration,
            EntityRelationMapFactory::INSTRUCTION_DEFAULT
        );
        $map = $factory->create();

        $expected = $this->expected['default'];
        static::assertEquals($expected, $map->export(true));
    }

    /**
     * @test
     */
    public function identityRelationsAreResolved()
    {
        $factory = new EntityRelationMapFactory(
            $this->configuration,
            EntityRelationMapFactory::INSTRUCTION_IDENTITY
        );
        $map = $factory->create();

        $expected = array_merge_recursive(
            $this->expected['default'],
            $this->expected['identity']
        );
        static::assertEquals($expected, $map->export(true));
    }

    /**
     * @test
     */
    public function oppositeRelationsAreResolved()
    {
        $factory = new EntityRelationMapFactory(
            $this->configuration,
            EntityRelationMapFactory::INSTRUCTION_OPPOSITE
        );
        $map = $factory->create();

        $expectedDefault = $this->expected['default'];
        $expectedDefault['content']['categories'] = [];
        $expected = array_merge_recursive(
            $expectedDefault,
            $this->expected['opposite']
        );
        static::assertEquals($expected, $map->export(true));
    }

    /**
     * @test
     */
    public function allRelationsAreResolved()
    {
        $factory = new EntityRelationMapFactory(
            $this->configuration,
            EntityRelationMapFactory::INSTRUCTION_ALL
        );
        $map = $factory->create();

        $expectedDefault = $this->expected['default'];
        $expectedDefault['content']['categories'] = [];
        $expected = array_merge_recursive(
            $expectedDefault,
            $this->expected['identity'],
            $this->expected['opposite']
        );
        array_splice(
            $expected['category']['__identity']['passive'],
            array_search(
                'category.__identity <- content.categories',
                $expected['category']['__identity']['passive']
            ),
            1
        );
        static::assertEquals($expected, $map->export(true));
        var_export($map->export(true));
    }
}
