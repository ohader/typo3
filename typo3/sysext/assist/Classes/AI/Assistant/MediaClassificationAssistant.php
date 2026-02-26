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

use Symfony\Component\Uid\Uuid;
use TYPO3\CMS\Assist\AI\Agent\AgentCallRequest;
use TYPO3\CMS\Assist\AI\Message\AgentInputInterface;
use TYPO3\CMS\Assist\AI\Message\AgentOutputInterface;
use TYPO3\CMS\Assist\Attribute\AsAssistant;
use TYPO3\CMS\Assist\Domain\Enum\AssistantCapability;
use TYPO3\CMS\Assist\Domain\Enum\AssistantMode;
use TYPO3\CMS\Assist\Domain\Model\Step;
use TYPO3\CMS\Assist\Domain\Repository\ProgressRepository;
use TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\StorageRepository;

#[AsAssistant(
    identifier: 'typo3-assist-media-classification',
    mode: AssistantMode::module,
    capabilities: [AssistantCapability::messages, AssistantCapability::inputImage, AssistantCapability::toolCalling],
    triggerResources: ['sys_file'],
    triggerComponents: ['file-upload'],
    triggerRoutes: ['/module/file/list'],
    labelDomain: 'assist.assistants.media_classification',
)]
final readonly class MediaClassificationAssistant implements AssistantInterface
{
    public function __construct(
        private StorageRepository $storageRepository,
        private ProgressRepository $progressRepository,
        private TranslationConfigurationProvider $translationConfigurationProvider,
    ) {}

    public function getToolPolicy(): ?ToolPolicy
    {
        return null;
    }

    public function buildAgentCall(AgentInputInterface $input, AgentOutputInterface $output): ?AgentCallRequest
    {
        return new AgentCallRequest(
            model: $input->getModel(),
            messageBag: $input->getMessageBag(),
        );
    }

    public function process(AgentInputInterface $input, AgentOutputInterface $output): void {}

    public function handleClientRequest(AssistantRequest $request): AssistantResponse
    {
        $progressHeader = $request->headers['x-typo3-assist-progress'] ?? '';

        if ($progressHeader === '') {
            return $this->initializeProgress($request);
        }
        return $this->continueProgress($progressHeader, $request);
    }

    private function initializeProgress(AssistantRequest $request): AssistantResponse
    {
        $steps = $this->buildSteps();
        return new AssistantResponse(steps: $steps);
    }

    private function buildSteps(): array
    {
        $files = [];
        foreach ($this->storageRepository->findAll() as $storage) {
            $folder = $storage->getRootLevelFolder();
            foreach ($storage->getFilesInFolder($folder, recursive: true) as $file) {
                if ($file->getUid() > 0) {
                    $files[] = $file;
                }
            }
        }

        $languages = $this->translationConfigurationProvider->getSystemLanguages(0);

        $fileSubs = array_map(
            static fn(File $file): Step => new Step(
                identifier: $file->getIdentifier(),
                description: $file->getIdentifier(),
                subject: 'sys_file:' . $file->getUid(),
            ),
            $files,
        );

        $steps = [];
        foreach ($languages as $language) {
            $label = $language['uid'] === 0 ? 'default' : $language['title'];
            $steps[] = new Step(
                identifier: (string)$language['uid'],
                description: 'Resolve title, description and alternative text (language: ' . $label . ')',
                subject: '',
                subs: $fileSubs,
            );
        }

        return array_values($steps);
    }

    private function continueProgress(string $uuid, AssistantRequest $request): AssistantResponse
    {
        $progress = $this->progressRepository->findByUuid(Uuid::fromString($uuid));
        if ($progress === null) {
            return new AssistantResponse();
        }

        $steps = $this->deserializeSteps($request->params['steps'] ?? []);

        return new AssistantResponse(steps: $steps, progress: $progress);
    }

    private function deserializeSteps(array $raw): array
    {
        return array_map(
            function (array $item): Step {
                return new Step(
                    identifier: (string)($item['identifier'] ?? ''),
                    description: (string)($item['description'] ?? ''),
                    subject: (string)($item['subject'] ?? ''),
                    subs: $this->deserializeSteps($item['subs'] ?? []),
                    done: (bool)($item['done'] ?? false),
                );
            },
            $raw,
        );
    }
}
