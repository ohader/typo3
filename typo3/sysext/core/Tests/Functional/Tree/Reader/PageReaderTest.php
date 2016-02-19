<?php
namespace TYPO3\CMS\Core\Tests\Functional\Tree\Reader;

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

use TYPO3\CMS\Core\Tree\Driver\DriverInterface;
use TYPO3\CMS\Core\Tree\Reader\PageReader;

/**
 * Class ExportTest
 * @package TYPO3\CMS\Core\Tests\Functional\Tree
 */
class PageReaderTest extends \TYPO3\CMS\Core\Tests\FunctionalTestCase
{
    const BackendUser_Admin = 1;
    const BackendUser_EditorOne = 2;
    const BackendUser_EditorTwo = 3;

    const TYPO3_SiteName = 'New TYPO3 FunctionalTest Site';

    /**
     * @var PageReader
     */
    protected $subject;

    /**
     * Sets up this test case.
     * @throws \Exception
     */
    protected function setUp()
    {
        parent::setUp();
        $this->importMySqlXmlDataSet(dirname(__DIR__) . '/Fixtures/data.xml');
        $this->setUpLanguageService('default');

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] = static::TYPO3_SiteName;

        $this->subject = new PageReader();
    }

    /**
     * Tears down this test case.
     */
    protected function tearDown()
    {
        unset($this->subject);
    }

    /**
     * @param int $backendUserId
     * @param array $expected
     *
     * @test
     * @dataProvider getRootNodesReturnsValidNodesDataProvider
     */
    public function getRootNodesReturnsValidNodes($backendUserId, $expected)
    {
        $this->setUpBackendUser($backendUserId);

        $result = $this->subject->getRootNodes();
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function getRootNodesReturnsValidNodesDataProvider()
    {
        return [
            'admin user' => [
                static::BackendUser_Admin,
                [
                    [
                        'identifier' => 0,
                        'mountIndex' => 0,
                        'parent' => null,
                        'depth' => 0,
                        'label' => static::TYPO3_SiteName,
                        'expanded' => false,
                        'hasChildren' => true,
                        'icon' => '',
                    ]
                ]
            ],
            'editor1' => [
                static::BackendUser_EditorOne,
                [
                    [
                        'identifier' => '76',
                        'mountIndex' => 0,
                        'parent' => null,
                        'depth' => 0,
                        'label' => 'Pages For Editor 1',
                        'expanded' => false,
                        'hasChildren' => true,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '78',
                        'mountIndex' => 1,
                        'parent' => null,
                        'depth' => 0,
                        'label' => 'Mixed Permissions',
                        'expanded' => false,
                        'hasChildren' => true,
                        'icon' => '',
                    ]
                ]
            ],
            'editor2' => [
                static::BackendUser_EditorTwo,
                [
                        [
                            'identifier' => '77',
                            'mountIndex' => 0,
                            'parent' => null,
                            'depth' => 0,
                            'label' => 'Pages for Editor 2',
                            'expanded' => false,
                            'hasChildren' => true,
                            'icon' => '',
                        ],
                        [
                            'identifier' => '78',
                            'mountIndex' => 1,
                            'parent' => null,
                            'depth' => 0,
                            'label' => 'Mixed Permissions',
                            'expanded' => false,
                            'hasChildren' => true,
                            'icon' => '',
                        ]
                ]
            ],
        ];
    }

    /**
     * @param int $backendUserId
     * @param string $identifier
     * @param int $depth
     * @param array $expected
     *
     * @test
     * @dataProvider getReturnsValidNodesDataProvider
     */
    public function getReturnsValidNodes($backendUserId, $identifier, $depth, $expected)
    {
        $backendUser = $this->setUpBackendUser($backendUserId);
        unset($backendUser->uc['BackendComponents']['States']['Pagetree']);

        $result = $this->subject->get($identifier, $depth);
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function getReturnsValidNodesDataProvider()
    {
        return [
            'admin user' => [
                static::BackendUser_Admin,
                DriverInterface::IDENTIFIER_ROOT,
                null,
                [
                    [
                        'identifier' => 0,
                        'mountIndex' => 0,
                        'parent' => null,
                        'depth' => 0,
                        'label' => 'New TYPO3 FunctionalTest Site',
                        'expanded' => false,
                        'hasChildren' => true,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '1',
                        'mountIndex' => 0,
                        'parent' => 0,
                        'depth' => 1,
                        'label' => 'TestRoot',
                        'expanded' => false,
                        'hasChildren' => true,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '2',
                        'mountIndex' => 0,
                        'parent' => '1',
                        'depth' => 2,
                        'label' => 'PageTypes',
                        'expanded' => false,
                        'hasChildren' => true,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '5',
                        'mountIndex' => 0,
                        'parent' => '2',
                        'depth' => 3,
                        'label' => 'Standard Page',
                        'expanded' => false,
                        'hasChildren' => false,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '6',
                        'mountIndex' => 0,
                        'parent' => '2',
                        'depth' => 3,
                        'label' => 'BE User Section',
                        'expanded' => false,
                        'hasChildren' => false,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '7',
                        'mountIndex' => 0,
                        'parent' => '2',
                        'depth' => 3,
                        'label' => 'Shortcut',
                        'expanded' => false,
                        'hasChildren' => false,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '8',
                        'mountIndex' => 0,
                        'parent' => '2',
                        'depth' => 3,
                        'label' => 'Mount Point',
                        'expanded' => false,
                        'hasChildren' => false,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '9',
                        'mountIndex' => 0,
                        'parent' => '2',
                        'depth' => 3,
                        'label' => 'External URL',
                        'expanded' => false,
                        'hasChildren' => false,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '10',
                        'mountIndex' => 0,
                        'parent' => '2',
                        'depth' => 3,
                        'label' => 'Sysfolder',
                        'expanded' => false,
                        'hasChildren' => false,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '11',
                        'mountIndex' => 0,
                        'parent' => '2',
                        'depth' => 3,
                        'label' => 'Recycler',
                        'expanded' => false,
                        'hasChildren' => false,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '12',
                        'mountIndex' => 0,
                        'parent' => '2',
                        'depth' => 3,
                        'label' => 'Divider',
                        'expanded' => false,
                        'hasChildren' => false,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '3',
                        'mountIndex' => 0,
                        'parent' => '1',
                        'depth' => 2,
                        'label' => 'PageStati',
                        'expanded' => false,
                        'hasChildren' => true,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '18',
                        'mountIndex' => 0,
                        'parent' => '3',
                        'depth' => 3,
                        'label' => 'SiteRoot',
                        'expanded' => false,
                        'hasChildren' => false,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '13',
                        'mountIndex' => 0,
                        'parent' => '3',
                        'depth' => 3,
                        'label' => 'Hidden',
                        'expanded' => false,
                        'hasChildren' => true,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '26',
                        'mountIndex' => 0,
                        'parent' => '13',
                        'depth' => 4,
                        'label' => 'Standard Page',
                        'expanded' => false,
                        'hasChildren' => false,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '25',
                        'mountIndex' => 0,
                        'parent' => '13',
                        'depth' => 4,
                        'label' => 'BE User Section',
                        'expanded' => false,
                        'hasChildren' => false,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '24',
                        'mountIndex' => 0,
                        'parent' => '13',
                        'depth' => 4,
                        'label' => 'Shortcut',
                        'expanded' => false,
                        'hasChildren' => false,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '23',
                        'mountIndex' => 0,
                        'parent' => '13',
                        'depth' => 4,
                        'label' => 'Mount Point',
                        'expanded' => false,
                        'hasChildren' => false,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '22',
                        'mountIndex' => 0,
                        'parent' => '13',
                        'depth' => 4,
                        'label' => 'External URL',
                        'expanded' => false,
                        'hasChildren' => false,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '21',
                        'mountIndex' => 0,
                        'parent' => '13',
                        'depth' => 4,
                        'label' => 'Sysfolder',
                        'expanded' => false,
                        'hasChildren' => false,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '20',
                        'mountIndex' => 0,
                        'parent' => '13',
                        'depth' => 4,
                        'label' => 'Recycler',
                        'expanded' => false,
                        'hasChildren' => false,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '19',
                        'mountIndex' => 0,
                        'parent' => '13',
                        'depth' => 4,
                        'label' => 'Divider',
                        'expanded' => false,
                        'hasChildren' => false,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '14',
                        'mountIndex' => 0,
                        'parent' => '3',
                        'depth' => 3,
                        'label' => 'ACL',
                        'expanded' => false,
                        'hasChildren' => true,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '58',
                        'mountIndex' => 0,
                        'parent' => '14',
                        'depth' => 4,
                        'label' => 'Standard Page (subpages)',
                        'expanded' => false,
                        'hasChildren' => false,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '57',
                        'mountIndex' => 0,
                        'parent' => '14',
                        'depth' => 4,
                        'label' => 'BE User Section (subpages)',
                        'expanded' => false,
                        'hasChildren' => false,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '56',
                        'mountIndex' => 0,
                        'parent' => '14',
                        'depth' => 4,
                        'label' => 'Shortcut (subpages)',
                        'expanded' => false,
                        'hasChildren' => false,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '55',
                        'mountIndex' => 0,
                        'parent' => '14',
                        'depth' => 4,
                        'label' => 'Mount Point (subpages)',
                        'expanded' => false,
                        'hasChildren' => false,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '54',
                        'mountIndex' => 0,
                        'parent' => '14',
                        'depth' => 4,
                        'label' => 'External URL (subpages)',
                        'expanded' => false,
                        'hasChildren' => false,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '51',
                        'mountIndex' => 0,
                        'parent' => '14',
                        'depth' => 4,
                        'label' => 'Divider (subpages)',
                        'expanded' => false,
                        'hasChildren' => false,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '34',
                        'mountIndex' => 0,
                        'parent' => '14',
                        'depth' => 4,
                        'label' => 'Standard Page',
                        'expanded' => false,
                        'hasChildren' => false,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '33',
                        'mountIndex' => 0,
                        'parent' => '14',
                        'depth' => 4,
                        'label' => 'BE User Section',
                        'expanded' => false,
                        'hasChildren' => false,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '32',
                        'mountIndex' => 0,
                        'parent' => '14',
                        'depth' => 4,
                        'label' => 'Shortcut',
                        'expanded' => false,
                        'hasChildren' => false,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '31',
                        'mountIndex' => 0,
                        'parent' => '14',
                        'depth' => 4,
                        'label' => 'Mount Point',
                        'expanded' => false,
                        'hasChildren' => false,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '30',
                        'mountIndex' => 0,
                        'parent' => '14',
                        'depth' => 4,
                        'label' => 'External URL',
                        'expanded' => false,
                        'hasChildren' => false,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '27',
                        'mountIndex' => 0,
                        'parent' => '14',
                        'depth' => 4,
                        'label' => 'Divider',
                        'expanded' => false,
                        'hasChildren' => false,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '15',
                        'mountIndex' => 0,
                        'parent' => '3',
                        'depth' => 3,
                        'label' => 'Time',
                        'expanded' => false,
                        'hasChildren' => true,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '42',
                        'mountIndex' => 0,
                        'parent' => '15',
                        'depth' => 4,
                        'label' => 'Standard Page',
                        'expanded' => false,
                        'hasChildren' => false,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '41',
                        'mountIndex' => 0,
                        'parent' => '15',
                        'depth' => 4,
                        'label' => 'BE User Section',
                        'expanded' => false,
                        'hasChildren' => false,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '40',
                        'mountIndex' => 0,
                        'parent' => '15',
                        'depth' => 4,
                        'label' => 'Shortcut',
                        'expanded' => false,
                        'hasChildren' => false,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '39',
                        'mountIndex' => 0,
                        'parent' => '15',
                        'depth' => 4,
                        'label' => 'Mount Point',
                        'expanded' => false,
                        'hasChildren' => false,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '38',
                        'mountIndex' => 0,
                        'parent' => '15',
                        'depth' => 4,
                        'label' => 'External URL',
                        'expanded' => false,
                        'hasChildren' => false,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '35',
                        'mountIndex' => 0,
                        'parent' => '15',
                        'depth' => 4,
                        'label' => 'Divider',
                        'expanded' => false,
                        'hasChildren' => false,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '16',
                        'mountIndex' => 0,
                        'parent' => '3',
                        'depth' => 3,
                        'label' => 'Content From PID',
                        'expanded' => false,
                        'hasChildren' => false,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '17',
                        'mountIndex' => 0,
                        'parent' => '3',
                        'depth' => 3,
                        'label' => 'Hide In Menu',
                        'expanded' => false,
                        'hasChildren' => true,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '66',
                        'mountIndex' => 0,
                        'parent' => '17',
                        'depth' => 4,
                        'label' => 'Standard Page',
                        'expanded' => false,
                        'hasChildren' => false,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '65',
                        'mountIndex' => 0,
                        'parent' => '17',
                        'depth' => 4,
                        'label' => 'BE User Section',
                        'expanded' => false,
                        'hasChildren' => false,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '64',
                        'mountIndex' => 0,
                        'parent' => '17',
                        'depth' => 4,
                        'label' => 'Shortcut',
                        'expanded' => false,
                        'hasChildren' => false,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '63',
                        'mountIndex' => 0,
                        'parent' => '17',
                        'depth' => 4,
                        'label' => 'Mount Point',
                        'expanded' => false,
                        'hasChildren' => false,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '62',
                        'mountIndex' => 0,
                        'parent' => '17',
                        'depth' => 4,
                        'label' => 'External URL',
                        'expanded' => false,
                        'hasChildren' => false,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '59',
                        'mountIndex' => 0,
                        'parent' => '17',
                        'depth' => 4,
                        'label' => 'Divider',
                        'expanded' => false,
                        'hasChildren' => false,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '67',
                        'mountIndex' => 0,
                        'parent' => '17',
                        'depth' => 4,
                        'label' => 'Content From PID',
                        'expanded' => false,
                        'hasChildren' => false,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '4',
                        'mountIndex' => 0,
                        'parent' => '1',
                        'depth' => 2,
                        'label' => 'Permissions',
                        'expanded' => false,
                        'hasChildren' => true,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '76',
                        'mountIndex' => 0,
                        'parent' => '4',
                        'depth' => 3,
                        'label' => 'Pages For Editor 1',
                        'expanded' => false,
                        'hasChildren' => true,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '79',
                        'mountIndex' => 0,
                        'parent' => '76',
                        'depth' => 4,
                        'label' => 'Subpage 1a',
                        'expanded' => false,
                        'hasChildren' => false,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '80',
                        'mountIndex' => 0,
                        'parent' => '76',
                        'depth' => 4,
                        'label' => 'Subpage 2a',
                        'expanded' => false,
                        'hasChildren' => false,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '81',
                        'mountIndex' => 0,
                        'parent' => '76',
                        'depth' => 4,
                        'label' => 'Subpage 3a',
                        'expanded' => false,
                        'hasChildren' => false,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '77',
                        'mountIndex' => 0,
                        'parent' => '4',
                        'depth' => 3,
                        'label' => 'Pages for Editor 2',
                        'expanded' => false,
                        'hasChildren' => true,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '82',
                        'mountIndex' => 0,
                        'parent' => '77',
                        'depth' => 4,
                        'label' => 'Subpage 1b',
                        'expanded' => false,
                        'hasChildren' => false,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '83',
                        'mountIndex' => 0,
                        'parent' => '77',
                        'depth' => 4,
                        'label' => 'Subpage 2b',
                        'expanded' => false,
                        'hasChildren' => false,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '84',
                        'mountIndex' => 0,
                        'parent' => '77',
                        'depth' => 4,
                        'label' => 'Subpage 3b',
                        'expanded' => false,
                        'hasChildren' => false,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '78',
                        'mountIndex' => 0,
                        'parent' => '4',
                        'depth' => 3,
                        'label' => 'Mixed Permissions',
                        'expanded' => false,
                        'hasChildren' => true,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '85',
                        'mountIndex' => 0,
                        'parent' => '78',
                        'depth' => 4,
                        'label' => 'Page for Editor 1',
                        'expanded' => false,
                        'hasChildren' => false,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '86',
                        'mountIndex' => 0,
                        'parent' => '78',
                        'depth' => 4,
                        'label' => 'Page for Editor 2',
                        'expanded' => false,
                        'hasChildren' => false,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '87',
                        'mountIndex' => 0,
                        'parent' => '78',
                        'depth' => 4,
                        'label' => 'Page for Both',
                        'expanded' => false,
                        'hasChildren' => false,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '68',
                        'mountIndex' => 0,
                        'parent' => '1',
                        'depth' => 2,
                        'label' => 'Workspaces',
                        'expanded' => false,
                        'hasChildren' => true,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '69',
                        'mountIndex' => 0,
                        'parent' => '68',
                        'depth' => 3,
                        'label' => 'Created Live, Deleted in WS',
                        'expanded' => false,
                        'hasChildren' => false,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '88',
                        'mountIndex' => 0,
                        'parent' => '1',
                        'depth' => 2,
                        'label' => 'Categories',
                        'expanded' => false,
                        'hasChildren' => false,
                        'icon' => '',
                    ],
                ]
            ],
            'editor1' => [
                static::BackendUser_EditorOne,
                DriverInterface::IDENTIFIER_ROOT,
                null,
                [
                    [
                        'identifier' => '76',
                        'mountIndex' => 0,
                        'parent' => NULL,
                        'depth' => 0,
                        'label' => 'Pages For Editor 1',
                        'expanded' => false,
                        'hasChildren' => true,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '79',
                        'mountIndex' => 0,
                        'parent' => '76',
                        'depth' => 1,
                        'label' => 'Subpage 1a',
                        'expanded' => false,
                        'hasChildren' => false,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '80',
                        'mountIndex' => 0,
                        'parent' => '76',
                        'depth' => 1,
                        'label' => 'Subpage 2a',
                        'expanded' => false,
                        'hasChildren' => false,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '81',
                        'mountIndex' => 0,
                        'parent' => '76',
                        'depth' => 1,
                        'label' => 'Subpage 3a',
                        'expanded' => false,
                        'hasChildren' => false,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '78',
                        'mountIndex' => 1,
                        'parent' => NULL,
                        'depth' => 0,
                        'label' => 'Mixed Permissions',
                        'expanded' => false,
                        'hasChildren' => true,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '85',
                        'mountIndex' => 0,
                        'parent' => '78',
                        'depth' => 1,
                        'label' => 'Page for Editor 1',
                        'expanded' => false,
                        'hasChildren' => false,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '87',
                        'mountIndex' => 0,
                        'parent' => '78',
                        'depth' => 1,
                        'label' => 'Page for Both',
                        'expanded' => false,
                        'hasChildren' => false,
                        'icon' => '',
                    ],
                ]
            ],
            'editor2' => [
                static::BackendUser_EditorTwo,
                DriverInterface::IDENTIFIER_ROOT,
                null,
                [
                    [
                        'identifier' => '77',
                        'mountIndex' => 0,
                        'parent' => NULL,
                        'depth' => 0,
                        'label' => 'Pages for Editor 2',
                        'expanded' => false,
                        'hasChildren' => true,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '82',
                        'mountIndex' => 0,
                        'parent' => '77',
                        'depth' => 1,
                        'label' => 'Subpage 1b',
                        'expanded' => false,
                        'hasChildren' => false,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '83',
                        'mountIndex' => 0,
                        'parent' => '77',
                        'depth' => 1,
                        'label' => 'Subpage 2b',
                        'expanded' => false,
                        'hasChildren' => false,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '84',
                        'mountIndex' => 0,
                        'parent' => '77',
                        'depth' => 1,
                        'label' => 'Subpage 3b',
                        'expanded' => false,
                        'hasChildren' => false,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '78',
                        'mountIndex' => 1,
                        'parent' => NULL,
                        'depth' => 0,
                        'label' => 'Mixed Permissions',
                        'expanded' => false,
                        'hasChildren' => true,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '86',
                        'mountIndex' => 0,
                        'parent' => '78',
                        'depth' => 1,
                        'label' => 'Page for Editor 2',
                        'expanded' => false,
                        'hasChildren' => false,
                        'icon' => '',
                    ],
                    [
                        'identifier' => '87',
                        'mountIndex' => 0,
                        'parent' => '78',
                        'depth' => 1,
                        'label' => 'Page for Both',
                        'expanded' => false,
                        'hasChildren' => false,
                        'icon' => '',
                    ],
                ]
            ],
        ];
    }

    /**
     * @param int $backendUserId
     * @param string $identifier
     * @param int $expected
     * @throws \TYPO3\CMS\Core\Tests\Exception
     *
     * @test
     * @dataProvider getDepthReturnsValidValueDataProvider
     */
    public function getDepthReturnsValidValue($backendUserId, $identifier, $expected)
    {
        $this->setUpBackendUser($backendUserId);

        $result = $this->subject->getDepth($identifier);
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function getDepthReturnsValidValueDataProvider()
    {
        return [
            'admin user, root level,' => [
                static::BackendUser_Admin, 0, 0
            ],
            'admin user, third level' => [
                static::BackendUser_Admin, 78, 3
            ],
            'editor1, mount-point' => [
                static::BackendUser_EditorOne, 78, 0
            ],
            'editor2, mount-point' => [
                static::BackendUser_EditorTwo, 78, 0
            ],
            'editor1, mount-point first level' => [
                static::BackendUser_EditorOne, 87, 1
            ],
            'editor2, mount-point first level' => [
                static::BackendUser_EditorTwo, 87, 1
            ],
        ];
    }
}