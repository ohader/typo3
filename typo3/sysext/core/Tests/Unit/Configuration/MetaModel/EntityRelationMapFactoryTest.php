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
    protected $configuration;

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
                            'special' => 'language',
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
                            'special' => 'language',
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
                ],
            ],
        ];
    }

    /**
     * @test
     */
    public function relationsAreResolved()
    {
        $factory = new EntityRelationMapFactory($this->configuration);
        $map = $factory->create();

        $expected = [
            'content' => [
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
                        'content.imageComposition -> image',
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
                'content' => [
                    'passive' => [
                        'image.content <- content.imageComposition',
                    ],
                ],
            ],
        ];

        static::assertSame($expected, $map->export(true));
    }
}
