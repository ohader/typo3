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

namespace TYPO3\CMS\Assist\Tests\Functional;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use TYPO3\CMS\Assist\AI\Platform\PlatformBridge;
use TYPO3\CMS\Assist\AI\Platform\PlatformConnector;
use TYPO3\CMS\Assist\AI\Platform\PlatformResolver;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class NumbTest extends FunctionalTestCase
{
    use AssistBasedTestTrait;

    protected array $coreExtensionsToLoad = ['assist'];
    private PlatformConnector $platformConnector;
    private PlatformResolver $platformResolver;
    private PlatformBridge $bridge;

    public function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/pages.csv');
        $this->buildAssistSiteConfiguration(
            'PlatformBridgeTest',
            1,
            '/',
            [self::ASSIST_PLATFORM_NUMB_ENCORE]
        );
        $this->platformConnector = $this->get(PlatformConnector::class);
        $this->platformResolver = $this->get(PlatformResolver::class);
        $platform = $this->platformResolver->getSitePlatform(
            'PlatformBridgeTest',
            'typo3/symfony-ai-numb-platform'
        );
        $this->bridge = $this->platformConnector->buildBridge($platform);
    }

    public static function canSendAndReceiveMessageToPlatformDataProvider(): iterable
    {
        yield ['ping', 'pong'];
        yield ['Who are you?', 'My name is Numb-Encore. Nice to meet you!'];
        yield ['What is the current time?', 'I cannot help with "What is the current time?"'];
        yield ['How much is 1+1?', 'I cannot help with "How much is 1+1?"'];
    }

    #[Test]
    #[DataProvider('canSendAndReceiveMessageToPlatformDataProvider')]
    public function canSendAndReceiveMessageToPlatform(string $payload, string $expectation): void
    {
        $result = $this->bridge->getPlatformFactory()->invoke(
            self::ASSIST_MODEL_NUMB_ENCORE,
            new MessageBag(Message::ofUser($payload))
        );
        self::assertSame($expectation, $result->asText());
    }
}
