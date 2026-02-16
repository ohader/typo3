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
use TYPO3\CMS\Assist\Domain\Enum\ProgressItemType;
use TYPO3\CMS\Assist\Domain\Model\ProgressItem;
use TYPO3\CMS\Assist\Domain\Repository\ProgressRepository;
use TYPO3\CMS\Assist\Service\ConfigurationResolver;

/**
 * Accepts an {@see AgentBag}, constructs a Symfony AI {@see Agent},
 * invokes it, and persists both the submitted user message and the
 * received assistant response.
 *
 * @internal
 */
final readonly class AgentConnector
{
    public function __construct(
        private ConfigurationResolver $configurationResolver,
        private PlatformConnector $platformConnector,
        private ProgressRepository $progressRepository,
    ) {}

    public function call(AgentBag $agentBag): ResultInterface
    {
        $platform = null;
        foreach ($this->configurationResolver->getDefaultPlatforms() as $candidate) {
            if ($candidate->package === $agentBag->model->platform) {
                $platform = $candidate;
                break;
            }
        }
        if ($platform === null) {
            throw new \RuntimeException(
                sprintf('No platform found for package "%s".', $agentBag->model->platform),
                1739693001,
            );
        }

        $bridge = $this->platformConnector->buildBridge($platform);
        $livePlatform = $bridge->getPlatformFactory();

        $agent = new Agent(
            platform: $livePlatform,
            model: $agentBag->model->model,
            inputProcessors: $agentBag->inputProcessors,
            outputProcessors: $agentBag->outputProcessors,
        );

        $sequencePointer = $agentBag->sequencePointer;
        $progress = $agentBag->progress;

        // Persist submitted items (user messages not yet in the database)
        $items = $progress->items;
        for ($i = $sequencePointer->submitted(), $end = count($items); $i < $end; $i++) {
            $sequence = $this->progressRepository->append(
                uuid: $progress->uuid,
                model: $progress->model,
                initiator: $progress->initiator,
                item: $items[$i],
            );
            $sequencePointer->advanceProcessedTo($sequence);
        }

        $messageBag = $agentBag->toMessageBag();
        $result = $agent->call($messageBag);

        // Persist received response
        $sequence = $this->progressRepository->append(
            uuid: $progress->uuid,
            model: $progress->model,
            initiator: $progress->initiator,
            item: new ProgressItem(
                type: ProgressItemType::received,
                payload: $result->getContent(),
            ),
        );
        $sequencePointer->advanceProcessedTo($sequence);

        return $result;
    }
}
