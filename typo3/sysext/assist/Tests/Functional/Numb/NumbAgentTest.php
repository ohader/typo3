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
use TYPO3\CMS\Assist\AI\Agent\AgentCallRequest;
use TYPO3\CMS\Assist\AI\Agent\AgentGateway;
use TYPO3\CMS\Assist\AI\Platform\PlatformModel;
use TYPO3\CMS\Assist\Tests\Functional\AssistBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class NumbAgentTest extends FunctionalTestCase
{
    use AssistBasedTestTrait;

    protected array $coreExtensionsToLoad = ['assist'];
    private AgentGateway $agentGateway;
    private PlatformModel $model;

    public function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $this->buildAssistSiteConfiguration('NumbAgentTest', 1, '/', [self::ASSIST_PLATFORM_NUMB_ENCORE]);
        $this->agentGateway = $this->get(AgentGateway::class);
        $this->model = PlatformModel::fromString(
            self::ASSIST_MODEL_NUMB_ENCORE . '@' . self::ASSIST_PACKAGE_NUMB_ENCORE
        );
    }

    public static function canSendAndReceiveMessageViaAgentGatewayDataProvider(): iterable
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
    #[DataProvider('canSendAndReceiveMessageViaAgentGatewayDataProvider')]
    public function canSendAndReceiveMessageViaAgentGateway(string $payload, string $expectation): void
    {
        $result = $this->agentGateway->call(
            new AgentCallRequest(
                model: $this->model,
                messageBag: new MessageBag(Message::ofUser($payload)),
            )
        );
        self::assertSame($expectation, $result->getContent());
    }

    #[Test]
    public function canSendImageViaAgentGateway(): void
    {
        // 1×1 white pixel PNG — no filesystem dependency
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI6QAAAABJRU5ErkJggg==');
        $image = new Image($png, 'image/png');

        $result = $this->agentGateway->call(
            new AgentCallRequest(
                model: $this->model,
                messageBag: new MessageBag(
                    Message::ofUser($image, 'Describe this image and suggest an appropriate alt text for it.')
                ),
            )
        );

        self::assertSame(
            'The image shows a plain white rectangle with no visible content. Alt text: "Blank white image".',
            $result->getContent()
        );
    }
}
