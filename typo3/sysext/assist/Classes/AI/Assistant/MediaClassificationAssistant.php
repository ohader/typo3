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

use Symfony\AI\Platform\Message\Message;
use Symfony\Component\Uid\Uuid;
use TYPO3\CMS\Assist\AI\Agent\AgentCallRequest;
use TYPO3\CMS\Assist\AI\Agent\SequencePointer;
use TYPO3\CMS\Assist\AI\Assistant\Feedback\ConfirmationFeedback;
use TYPO3\CMS\Assist\AI\Assistant\Feedback\OptionItem;
use TYPO3\CMS\Assist\AI\Assistant\Feedback\OptionsFeedback;
use TYPO3\CMS\Assist\AI\Assistant\Feedback\ResultConverter;
use TYPO3\CMS\Assist\AI\Assistant\Feedback\TextFeedback;
use TYPO3\CMS\Assist\AI\Message\AgentInput;
use TYPO3\CMS\Assist\AI\Message\AgentInputInterface;
use TYPO3\CMS\Assist\AI\Message\AgentOutput;
use TYPO3\CMS\Assist\AI\Message\AgentOutputInterface;
use TYPO3\CMS\Assist\Attribute\AsAssistant;
use TYPO3\CMS\Assist\Domain\Enum\AssistantCapability;
use TYPO3\CMS\Assist\Domain\Enum\ProgressItemType;
use TYPO3\CMS\Assist\Domain\Model\Initiator;
use TYPO3\CMS\Assist\Domain\Model\Progress;
use TYPO3\CMS\Assist\Domain\Model\ProgressItem;
use TYPO3\CMS\Assist\Domain\Model\Step;
use TYPO3\CMS\Assist\Domain\Repository\ProgressRepository;
use TYPO3\CMS\Assist\Service\AssistantRegistry;
use TYPO3\CMS\Assist\Service\ConfigurationResolver;
use TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileType;
use TYPO3\CMS\Core\Resource\StorageRepository;

#[AsAssistant(
    identifier: 'typo3-assist-media-classification',
    capabilities: [AssistantCapability::messages, AssistantCapability::inputImage, AssistantCapability::toolCalling],
    triggerResources: ['sys_file'],
    triggerComponents: ['file-upload'],
    triggerRoutes: ['/module/file/list'],
    labelDomain: 'assist.assistants.media_classification',
)]
final readonly class MediaClassificationAssistant implements AssistantInterface
{
    use AssistantContextTrait;

    public function __construct(
        private StorageRepository $storageRepository,
        private ProgressRepository $progressRepository,
        private TranslationConfigurationProvider $translationConfigurationProvider,
        private AssistantOrchestrator $orchestrator,
        private AssistantRegistry $assistantRegistry,
        private ConfigurationResolver $configurationResolver,
        private ResultConverter $resultConverter,
        private ConnectionPool $connectionPool,
    ) {}

    public function getSystemPrompt(): ?string
    {
        return implode("\n", [
            'You are a TYPO3 CMS media classification assistant.',
            'Your task is to analyse images and generate metadata in the requested language.',
            'For each image produce a title (short), description (one sentence), and alternative text (screen-reader caption).',
            '',
            'Respond only in JSON matching this schema:',
            Type\UnionAggregate::of(
                Type\TextType::class,
                Type\ListAggregate::of(Type\StructureType::class),
            ),
        ]);
    }

    public function getToolPolicy(): ?ToolPolicy
    {
        return null;
    }

    public function buildAgentCall(AgentInputInterface $input, AgentOutputInterface $output): ?AgentCallRequest
    {
        return new AgentCallRequest(
            model: $input->getModel(),
            messageBag: $input->getMessageBag(),
            progress: $input->getProgress(),
            sequencePointer: $input->getSequencePointer(),
        );
    }

    public function process(AgentInputInterface $input, AgentOutputInterface $output): void {}

    public function handleClientRequest(AssistantRequest $request): AssistantResponse
    {
        $progressUuid = $request->getProgressUuid();

        if ($progressUuid !== null) {
            return $this->continueProgress($progressUuid, $request);
        }

        if (isset($request->params['language']) || isset($request->params['message'])) {
            return $this->startConversation($request);
        }

        return $this->initializeProgress($request);
    }

    private function initializeProgress(AssistantRequest $request): AssistantResponse
    {
        $steps = $this->buildSteps();
        $options = array_slice($this->buildInitialOptionItems(), 0, 4);

        if (count($options) >= 2) {
            $feedback = [new OptionsFeedback(key: 'language', text: 'Select a language to process:', options: $options)];
        } elseif (count($options) === 1) {
            $feedback = [new ConfirmationFeedback(
                text: $options[0]->text,
                acceptLabel: 'Start',
                declineLabel: 'Cancel',
            )];
        } else {
            $feedback = [new TextFeedback('All image files are already fully classified.')];
        }

        return new AssistantResponse(steps: $steps, feedback: $feedback);
    }

    private function startConversation(AssistantRequest $request): AssistantResponse
    {
        $model = $this->configurationResolver->getDefaultAssistantModel('typo3-assist-media-classification');
        if ($model === null) {
            return new AssistantResponse(feedback: [new TextFeedback('No model configured for this assistant.')]);
        }

        $languageUid = $this->resolveLanguageUid($request);
        if ($languageUid === null) {
            return new AssistantResponse(feedback: [new TextFeedback('Unable to determine language to process.')]);
        }

        $languages = $this->translationConfigurationProvider->getSystemLanguages(0);
        $language = $languages[$languageUid] ?? null;
        $languageLabel = $languageUid === 0 ? 'Default' : ($language['title'] ?? 'Language ' . $languageUid);
        $message = sprintf(
            'Classify image metadata (title, description, alternative text) for language: %s',
            $languageLabel,
        );

        $progress = new Progress(
            uuid: Uuid::v4(),
            model: $model,
            initiator: new Initiator(type: 'assistant', subject: $this->resolveAssistantAttribute($this)->identifier),
            userId: $this->getBackendUserId(),
            items: [new ProgressItem(ProgressItemType::submitted, $message)],
        );
        $this->progressRepository->add($progress);

        $input = new AgentInput($model, Message::ofUser($message));
        $input->progress = $progress;
        $input->sequencePointer = new SequencePointer(submitted: 1);
        $output = new AgentOutput();

        $assistant = $this->assistantRegistry->getAssistant('typo3-assist-media-classification');
        $this->orchestrator->process($assistant, $input, $output);

        $feedback = array_map(
            fn($result) => $this->resultConverter->convert($result),
            $output->getResultBag()->getResults(),
        );

        return new AssistantResponse(feedback: $feedback, progress: $progress);
    }

    private function resolveLanguageUid(AssistantRequest $request): ?int
    {
        if (isset($request->params['language'])) {
            return (int)$request->params['language'];
        }

        // Fallback for single-language confirmation flow: re-query to find the only option
        $options = $this->buildInitialOptionItems();
        if (count($options) === 1) {
            return (int)$options[0]->identifier;
        }

        return null;
    }

    /**
     * @return list<OptionItem>
     */
    private function buildInitialOptionItems(): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_file_metadata');
        $queryBuilder->getRestrictions()->removeAll();

        $rows = $queryBuilder
            ->select('sfm.sys_language_uid')
            ->addSelectLiteral('COUNT(DISTINCT sfm.file) AS missing_count')
            ->from('sys_file_metadata', 'sfm')
            ->innerJoin(
                'sfm',
                'sys_file',
                'sf',
                $queryBuilder->expr()->eq('sf.uid', $queryBuilder->quoteIdentifier('sfm.file')),
            )
            ->where(
                $queryBuilder->expr()->eq(
                    'sf.type',
                    $queryBuilder->createNamedParameter(FileType::IMAGE->value, Connection::PARAM_INT),
                ),
                $queryBuilder->expr()->gte(
                    'sfm.sys_language_uid',
                    $queryBuilder->createNamedParameter(0, Connection::PARAM_INT),
                ),
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->isNull('sfm.title'),
                    $queryBuilder->expr()->eq('sfm.title', $queryBuilder->createNamedParameter('')),
                    $queryBuilder->expr()->isNull('sfm.description'),
                    $queryBuilder->expr()->eq('sfm.description', $queryBuilder->createNamedParameter('')),
                    $queryBuilder->expr()->isNull('sfm.alternative'),
                    $queryBuilder->expr()->eq('sfm.alternative', $queryBuilder->createNamedParameter('')),
                ),
            )
            ->groupBy('sfm.sys_language_uid')
            ->orderBy('sfm.sys_language_uid', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();

        if ($rows === []) {
            return [];
        }

        $languages = $this->translationConfigurationProvider->getSystemLanguages(0);
        $languageMap = array_column($languages, null, 'uid');

        $options = [];
        foreach ($rows as $row) {
            $langUid = (int)$row['sys_language_uid'];
            $count = (int)$row['missing_count'];
            $language = $languageMap[$langUid] ?? null;
            $label = $langUid === 0 ? 'Default' : ($language['title'] ?? 'Language ' . $langUid);
            $options[] = new OptionItem(
                identifier: (string)$langUid,
                text: sprintf('Process %s (%d %s)', $label, $count, $count === 1 ? 'file' : 'files'),
                details: $language['ISOcode'] ?? null,
            );
        }

        return $options;
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

    private function continueProgress(Uuid $uuid, AssistantRequest $request): AssistantResponse
    {
        $progress = $this->progressRepository->findByUuid($uuid);
        if ($progress === null) {
            return new AssistantResponse();
        }

        $messages = [];
        foreach ($progress->items as $item) {
            $text = (string)json_decode((string)$item->payload);
            $messages[] = $item->type === ProgressItemType::submitted
                ? Message::ofUser($text)
                : Message::ofAssistant($text);
        }

        $newMessage = (string)($request->params['message'] ?? '');
        if ($newMessage !== '') {
            $this->progressRepository->append(
                uuid: $progress->uuid,
                item: new ProgressItem(ProgressItemType::submitted, $newMessage),
            );
            $messages[] = Message::ofUser($newMessage);
        }

        $model = $progress->model;
        $input = new AgentInput($model, ...$messages);
        $input->progress = $progress;
        $input->sequencePointer = new SequencePointer(submitted: count($progress->items));
        $output = new AgentOutput();

        $assistant = $this->assistantRegistry->getAssistant('typo3-assist-media-classification');
        $this->orchestrator->process($assistant, $input, $output);

        $feedback = array_map(
            fn($result) => $this->resultConverter->convert($result),
            $output->getResultBag()->getResults(),
        );

        $steps = $this->deserializeSteps($request->params['steps'] ?? []);
        return new AssistantResponse(steps: $steps, feedback: $feedback, progress: $progress);
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
