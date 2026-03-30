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

namespace TYPO3\CMS\Assist\Tests\Functional\AI\Platform;

use PHPUnit\Framework\Attributes\Test;
use Symfony\AI\Platform\Capability;
use TYPO3\CMS\Assist\AI\Platform\PlatformConnector;
use TYPO3\CMS\Assist\Service\ConfigurationResolver;
use TYPO3\CMS\Assist\Tests\Functional\AssistBasedTestTrait;
use TYPO3\Symfony\AI\NumbPlatform\Bridge\ChatModel;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class PlatformBridgeTest extends FunctionalTestCase
{
    use AssistBasedTestTrait;

    protected array $coreExtensionsToLoad = ['assist'];
    private PlatformConnector $platformConnector;
    private ConfigurationResolver $configurationResolver;

    public function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/pages.csv');
        $this->buildAssistSiteConfiguration(
            'PlatformBridgeTest',
            1,
            '/',
            [self::ASSIST_PLATFORM_NUMB_ENCORE]
        );
        $this->platformConnector = $this->get(PlatformConnector::class);
        $this->configurationResolver = $this->get(ConfigurationResolver::class);
    }

    #[Test]
    public function canResolveModels(): void
    {

        $platform = $this->configurationResolver->getSitePlatform(
            'PlatformBridgeTest',
            'typo3/symfony-ai-numb-platform'
        );
        $bridge = $this->platformConnector->buildBridge($platform);
        $modelCatalog = $bridge->getModelCatalog();
        $models = $modelCatalog->getModels();

        $model = $models[self::ASSIST_MODEL_NUMB_ENCORE] ?? null;
        self::assertArrayHasKey(self::ASSIST_MODEL_NUMB_ENCORE, $models);
        self::assertSame(ChatModel::class, $model['class']);
        self::assertContainsEquals(Capability::INPUT_MESSAGES, $model['capabilities']);
        self::assertContainsEquals(Capability::INPUT_TEXT, $model['capabilities']);
        self::assertContainsEquals(Capability::OUTPUT_TEXT, $model['capabilities']);
        self::assertContainsEquals(Capability::TOOL_CALLING, $model['capabilities']);
    }
}
