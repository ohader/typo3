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

namespace TYPO3\CMS\Assist\Tests\Functional\Service;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Assist\Service\PackageService;
use TYPO3\CMS\Assist\Tests\Functional\AssistBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class PackageServiceTest extends FunctionalTestCase
{
    use AssistBasedTestTrait;

    protected array $coreExtensionsToLoad = ['assist'];
    private PackageService $subject;

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
        $this->subject = $this->get(PackageService::class);
    }

    #[Test]
    public function canResolvePlatformPackages(): void
    {
        $packageNames = $this->subject->findPackageNamesByType(PackageService::SYMFONY_AI_PLATFORM);
        self::assertContainsEquals('typo3/symfony-ai-numb-platform', $packageNames);
    }

    #[Test]
    public function canResolveNumpPackage(): void
    {
        $package = $this->subject->getPackage('typo3/symfony-ai-numb-platform');
        self::assertSame('typo3/symfony-ai-numb-platform', $package['name']);
        self::assertSame(PackageService::SYMFONY_AI_PLATFORM, $package['type']);
    }
}
