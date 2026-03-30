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

namespace TYPO3\CMS\Assist\Tests\Functional\Numb;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\AI\Platform\Message\Content\Image;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Platform\Tool\ExecutionReference;
use Symfony\AI\Platform\Tool\Tool;
use TYPO3\CMS\Assist\AI\Platform\PlatformBridge;
use TYPO3\CMS\Assist\AI\Platform\PlatformConnector;
use TYPO3\CMS\Assist\Service\ConfigurationResolver;
use TYPO3\CMS\Assist\Tests\Functional\AssistBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class NumbPlatformTest extends FunctionalTestCase
{
    use AssistBasedTestTrait;

    protected array $coreExtensionsToLoad = ['assist'];
    private PlatformConnector $platformConnector;
    private ConfigurationResolver $configurationResolver;
    private PlatformBridge $bridge;

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
        $this->platformConnector = $this->get(PlatformConnector::class);
        $this->configurationResolver = $this->get(ConfigurationResolver::class);
        $platform = $this->configurationResolver->getSitePlatform(
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
        yield ['What is TYPO3 in one sentence?',
            'TYPO3 is an open-source enterprise content management system (CMS) for building scalable websites and web applications.'];
        yield ['Give me exactly 3 numbered taglines for a headless CMS product. Return only the numbered list.',
            "1. Your content, everywhere.\n2. Headless CMS, unlimited potential.\n3. Deliver content at the speed of thought."];
        yield ['Return a JSON object describing Berlin with fields: city, country, population (integer), famous_landmark (string).',
            '{"city":"Berlin","country":"Germany","population":3645000,"famous_landmark":"Brandenburg Gate"}'];
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

    #[Test]
    public function canInvokeToolCall(): void
    {
        $tool = new Tool(
            new ExecutionReference('stdClass'),
            'get_weather',
            'Get current weather for a location',
            [
                'type' => 'object',
                'properties' => [
                    'location' => ['type' => 'string', 'description' => 'City and country'],
                    'unit'     => ['type' => 'string', 'enum' => ['celsius', 'fahrenheit']],
                ],
                'required' => ['location'],
            ]
        );

        $result = $this->bridge->getPlatformFactory()->invoke(
            self::ASSIST_MODEL_NUMB_ENCORE,
            new MessageBag(Message::ofUser('What is the weather like in Berlin right now?')),
            ['tools' => [$tool]]
        );

        $toolCalls = $result->asToolCalls();
        self::assertCount(1, $toolCalls);
        self::assertSame('get_weather', $toolCalls[0]->getName());
    }

    #[Test]
    public function canSendImageToPlatform(): void
    {
        // 1×1 white pixel PNG — no filesystem dependency
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI6QAAAABJRU5ErkJggg==');
        $image = new Image($png, 'image/png');

        $result = $this->bridge->getPlatformFactory()->invoke(
            self::ASSIST_MODEL_NUMB_ENCORE,
            new MessageBag(Message::ofUser($image, 'Describe this image and suggest an appropriate alt text for it.'))
        );

        self::assertSame(
            'The image shows a plain white rectangle with no visible content. Alt text: "Blank white image".',
            $result->asText()
        );
    }
}
