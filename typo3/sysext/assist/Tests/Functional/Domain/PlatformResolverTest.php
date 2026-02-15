<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Assist\Tests\Functional\Domain;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Assist\Domain\Platform;
use TYPO3\CMS\Assist\Service\PlatformResolver;
use TYPO3\CMS\Assist\Tests\Functional\AssistBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class PlatformResolverTest extends FunctionalTestCase
{
    use AssistBasedTestTrait;

    protected array $coreExtensionsToLoad = ['assist'];
    private PlatformResolver $subject;

    public function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $this->buildAssistSiteConfiguration(
            'PlatformBridgeTest',
            1,
            '/',
            [self::ASSIST_PLATFORM_NUMB_ENCORE]
        );
        $this->subject = $this->get(PlatformResolver::class);
    }

    #[Test]
    public function canResolvePlatform(): void
    {
        $platform = $this->subject->getSitePlatform(
            'PlatformBridgeTest',
            'typo3/symfony-ai-numb-platform'
        );
        self::assertInstanceOf(Platform::class, $platform);
    }
}
