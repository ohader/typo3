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
        $this->importMySqlXmlDataSet(__DIR__ . '/Fixtures/data.xml');
        $this->setUpLanguageService('default');

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

        $this->assertSame(
            $expected,
            $this->subject->getRootNodes()
        );
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
                ]
            ],
            'editor1' => [
                static::BackendUser_EditorOne,
                [
                ]
            ],
            'editor2' => [
                static::BackendUser_EditorTwo,
                [
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
        $this->setUpBackendUser($backendUserId);

        $this->assertSame(
            $expected,
            $this->subject->get($identifier)
        );
    }

    /**
     * @return array
     */
    public function getReturnsValidNodesDataProvider()
    {
        return [
            'admin user' => [
                static::BackendUser_Admin,
                PageReader::IDENTIFIER_Root,
                null,
                [
                    [
                        'identifier' => '', 'parent' => '', 'name' => '', 'depth' => '', 'visual' => [ 'icon' => '', 'class' => '' ]
                    ]
                ]
            ],
            'editor1' => [
                static::BackendUser_EditorOne,
                PageReader::IDENTIFIER_Root,
                null,
                [
                ]
            ],
            'editor2' => [
                static::BackendUser_EditorTwo,
                PageReader::IDENTIFIER_Root,
                null,
                [
                ]
            ],
        ];
    }
}