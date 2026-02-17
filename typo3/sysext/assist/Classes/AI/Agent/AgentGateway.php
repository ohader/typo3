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

namespace TYPO3\CMS\Assist\AI\Agent;

use Symfony\AI\Agent\Agent;
use Symfony\AI\Platform\Result\ResultInterface;
use TYPO3\CMS\Assist\AI\Platform\PlatformConnector;
use TYPO3\CMS\Assist\Service\ConfigurationResolver;

/**
 * Accepts an {@see AgentCallRequest}, constructs a Symfony AI {@see Agent},
 * and invokes it.
 *
 * @internal
 */
final readonly class AgentGateway
{
    public function __construct(
        private ConfigurationResolver $configurationResolver,
        private PlatformConnector $platformConnector,
    ) {}

    public function call(AgentCallRequest $request): ResultInterface
    {
        $platform = null;
        foreach ($this->configurationResolver->getDefaultPlatforms() as $candidate) {
            if ($candidate->package === $request->model->platform) {
                $platform = $candidate;
                break;
            }
        }
        if ($platform === null) {
            throw new \RuntimeException(
                sprintf('No platform found for package "%s".', $request->model->platform),
                1739693001,
            );
        }

        $bridge = $this->platformConnector->buildBridge($platform);
        $livePlatform = $bridge->getPlatformFactory();

        $agent = new Agent(
            platform: $livePlatform,
            model: $request->model->model,
            inputProcessors: $request->inputProcessors,
            outputProcessors: $request->outputProcessors,
        );

        return $agent->call($request->messageBag);
    }
}
