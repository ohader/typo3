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

namespace TYPO3\CMS\Assist\AI\Assistant;

use Symfony\AI\Agent\Agent;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Assist\AI\Message\AgentInputInterface;
use TYPO3\CMS\Assist\AI\Message\AgentOutputInterface;
use TYPO3\CMS\Assist\AI\Platform\PlatformConnector;
use TYPO3\CMS\Assist\AI\Platform\PlatformModel;
use TYPO3\CMS\Assist\Domain\Enum\Availability;
use TYPO3\CMS\Assist\Domain\Model\Platform;
use TYPO3\CMS\Assist\Service\ConfigurationResolver;

#[Autoconfigure(public: true)]
final readonly class InlineChatAssistant implements AssistantInterface
{
    public function __construct(
        private ConfigurationResolver $configurationResolver,
        private PlatformConnector $platformConnector,
    ) {}

    public function process(AgentInputInterface $input, AgentOutputInterface $output): void
    {
        $platformModel = $input->getModel();
        $platform = $this->resolvePlatform($platformModel);
        $bridge = $this->platformConnector->buildBridge($platform);

        $agent = new Agent(
            platform: $bridge->getPlatformFactory(),
            model: $platformModel->model,
        );

        $result = $agent->call($input->getMessageBag());
        $output->add($result);
    }

    private function resolvePlatform(PlatformModel $platformModel): Platform
    {
        foreach ($this->configurationResolver->getDefaultPlatforms() as $platform) {
            if ($platform->package === $platformModel->platform && $platform->availability === Availability::enabled) {
                return $platform;
            }
        }
        throw new \RuntimeException(
            sprintf('No platform found for package "%s".', $platformModel->platform),
            1771200002,
        );
    }
}
