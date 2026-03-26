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

use Symfony\AI\Platform\Message\Content\Image;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Platform\Result\TextResult;
use Symfony\Component\Uid\Uuid;
use TYPO3\CMS\Assist\AI\Agent\AgentCallRequest;
use TYPO3\CMS\Assist\AI\Assistant\Feedback\ConfirmationFeedback;
use TYPO3\CMS\Assist\AI\Assistant\Feedback\ConfirmationItem;
use TYPO3\CMS\Assist\AI\Assistant\Feedback\ErrorFeedback;
use TYPO3\CMS\Assist\AI\Assistant\Feedback\MediaFeedback;
use TYPO3\CMS\Assist\AI\Assistant\Feedback\OptionItem;
use TYPO3\CMS\Assist\AI\Assistant\Feedback\OptionsFeedback;
use TYPO3\CMS\Assist\AI\Assistant\Feedback\QuickActionFeedback;
use TYPO3\CMS\Assist\AI\Assistant\Feedback\QuickActionItem;
use TYPO3\CMS\Assist\AI\Assistant\Feedback\ResultConverter;
use TYPO3\CMS\Assist\AI\Assistant\Feedback\TextFeedback;
use TYPO3\CMS\Assist\AI\Assistant\Type\AggregateInterface;
use TYPO3\CMS\Assist\AI\Assistant\Type\IntersectionAggregate;
use TYPO3\CMS\Assist\AI\Assistant\Type\OptionsAggregate;
use TYPO3\CMS\Assist\AI\Assistant\Type\PropertyDefinition;
use TYPO3\CMS\Assist\AI\Assistant\Type\StructureAggregate;
use TYPO3\CMS\Assist\AI\Assistant\Type\TypeInterface;
use TYPO3\CMS\Assist\AI\Message\AgentInput;
use TYPO3\CMS\Assist\AI\Message\AgentOutput;
use TYPO3\CMS\Assist\Attribute\AsAssistant;
use TYPO3\CMS\Assist\Domain\Enum\AssistantCapability;
use TYPO3\CMS\Assist\Domain\Model\Assistant;
use TYPO3\CMS\Assist\Domain\Model\Initiator;
use TYPO3\CMS\Assist\Domain\Model\Progress;
use TYPO3\CMS\Assist\Domain\Model\StateCollection;
use TYPO3\CMS\Assist\Domain\Model\Step;
use TYPO3\CMS\Assist\Domain\Model\StepCollection;
use TYPO3\CMS\Assist\Domain\Repository\ProgressRepository;
use TYPO3\CMS\Assist\Exception\StepSkippedException;
use TYPO3\CMS\Assist\Service\AssistantRegistry;
use TYPO3\CMS\Assist\Service\ConfigurationResolver;
use TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Resource\FileType;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

#[AsAssistant(
    identifier: self::IDENTIFIER,
    capabilities: [AssistantCapability::messages, AssistantCapability::inputImage, AssistantCapability::toolCalling],
    triggerResources: ['sys_file'],
    triggerComponents: ['file-upload'],
    triggerRoutes: ['/module/file/list'],
    labelDomain: 'assist.assistants.media_classification',
)]
final readonly class MediaClassificationAssistant implements AssistantInterface
{
    use AssistantContextTrait;
    private const IDENTIFIER = 'typo3-assist-media-classification';

    private const MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    private const ASPECT_LABELS = [
        'title' => 'Title',
        'description' => 'Description',
        'alternative' => 'Alt text',
    ];

    public function __construct(
        private ProgressRepository $progressRepository,
        private TranslationConfigurationProvider $translationConfigurationProvider,
        private AssistantOrchestrator $orchestrator,
        private AssistantRegistry $assistantRegistry,
        private ConfigurationResolver $configurationResolver,
        private ResultConverter $resultConverter,
        private ConnectionPool $connectionPool,
        private ResourceFactory $resourceFactory,
        private TcaSchemaFactory $schemaFactory,
    ) {}

    public function getAssistant(): Assistant
    {
        return $this->assistantRegistry->getAssistant(self::IDENTIFIER);
    }

    public function getSystemPrompt(): ?string
    {
        return null;
    }

    public function getToolPolicy(): ?ToolPolicy
    {
        return null;
    }

    public function buildAgentCall(AgentInput $input, AgentOutput $output): ?AgentCallRequest
    {
        return new AgentCallRequest(
            model: $input->model,
            messageBag: $input->messageBag,
            progress: $input->progress,
            sequencePointer: $input->sequencePointer,
        );
    }

    public function process(AgentInput $input, AgentOutput $output): void {}

    public function handleClientRequest(AssistantRequest $request): AssistantResponse
    {
        $progressUuid = $request->getProgressUuid();
        $stepIndex = $request->getStepIndex();

        $stepChoice = $request->params['step-choice'] ?? null;
        $languageChoice = $request->params['language-choice'] ?? null;
        $aspectChoice = $request->params['aspect-choice'] ?? null;

        if ($languageChoice === '') {
            return new AssistantResponse(feedback: [new TextFeedback("Cancelled. We're done here...")]);
        }

        // raise questions about what to do actually
        if ($progressUuid === null && $languageChoice === null) {
            return $this->initializeProcess();
        }

        // language chosen but aspect not yet selected → create progress (to carry language in state), show aspect options
        if ($progressUuid === null && MathUtility::canBeInterpretedAsInteger($languageChoice) && $aspectChoice === null) {
            $progress = $this->createProgress();
            // @todo steps persistence should be handled more generic in an upper layer
            $this->progressRepository->appendState(
                $progress->uuid,
                new StateCollection(['language-choice' => $languageChoice]),
            );
            return $this->showAspectOptions((int)$languageChoice, $progress);
        }

        if ($progressUuid === null) {
            return new AssistantResponse(feedback: [new ErrorFeedback('Unexpected state: No progress header submitted.')]);
        }
        $progress = $this->progressRepository->findByUuid($progressUuid);
        $languageChoice = $languageChoice ?? $progress->state?->get('language-choice');
        if (!MathUtility::canBeInterpretedAsInteger($languageChoice)) {
            return new AssistantResponse(feedback: [new ErrorFeedback('Unexpected state: No language choice submitted.')]);
        }

        // aspect-choice='' with progress means the user declined to switch aspect → offer new language
        if ($aspectChoice === '') {
            return $this->offerNewLanguage([]);
        }

        // aspect selected or switched within the same progress (same chat history)
        if ($aspectChoice !== null) {
            if (!array_key_exists($aspectChoice, self::ASPECT_LABELS)) {
                return new AssistantResponse(feedback: [new ErrorFeedback('Invalid aspect choice.')]);
            }
            $hadPreviousAspect = $progress->state?->get('aspect-choice') !== null;
            $newSteps = $this->buildSteps((int)$languageChoice, $aspectChoice);
            $this->progressRepository->appendSteps($progress->uuid, $newSteps);
            $this->progressRepository->appendState(
                $progress->uuid,
                new StateCollection(['language-choice' => $languageChoice, 'aspect-choice' => $aspectChoice]),
            );
            $progress = $this->progressRepository->findByUuid($progressUuid);
            return new AssistantResponse(
                feedback: [new TextFeedback($hadPreviousAspect
                    ? sprintf('Switching to %s...', self::ASPECT_LABELS[$aspectChoice])
                    : "Alright, let's get started...")],
                steps: $progress->steps,
                progress: $progress,
                boomerang: true,
            );
        }

        $aspectChoice = $progress->state?->get('aspect-choice');
        if ($aspectChoice === null || !array_key_exists($aspectChoice, self::ASPECT_LABELS)) {
            return new AssistantResponse(feedback: [new ErrorFeedback('Unexpected state: No aspect choice submitted.')]);
        }

        $step = $stepIndex !== null ? $progress->steps->find($stepIndex) : null;
        if ($stepIndex !== null && $stepChoice === null) {
            return new AssistantResponse(feedback: [new ErrorFeedback('Unexpected state: No step choice submitted.')]);
        }

        $feedback = [];
        if ($stepChoice === '') {
            // mark the step as done (skipped)
            $progress->steps->markDone($step);
            $this->progressRepository->appendSteps($progress->uuid, $progress->steps);
        } elseif ($stepChoice !== null) {
            $metadataStructure = $this->createMetadataStructure($aspectChoice);
            $decoded = $this->validateStepChoice($metadataStructure, $stepChoice);
            if ($decoded === null) {
                return new AssistantResponse(feedback: [new ErrorFeedback('Invalid step choice.')]);
            }
            $properties = array_filter(
                $decoded['value'],
                static fn(string $value): bool => $value !== null && $value !== '',
            );

            $metaDataQueryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_file_metadata');
            $metaData = $metaDataQueryBuilder
                ->select('uid')
                ->from('sys_file_metadata')
                ->where(
                    $metaDataQueryBuilder->expr()->eq('file', $metaDataQueryBuilder->createNamedParameter((int)$step->identifier, Connection::PARAM_INT)),
                    $metaDataQueryBuilder->expr()->eq('sys_language_uid', $metaDataQueryBuilder->createNamedParameter((int)$languageChoice, Connection::PARAM_INT)),
                )
                ->executeQuery()
                ->fetchAssociative();
            $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
            $dataHandler->start(['sys_file_metadata' => [(int)$metaData['uid'] => $properties]], []);
            $dataHandler->process_datamap();
            foreach ($dataHandler->errorLog as $error) {
                $feedback[] = new ErrorFeedback($error);
            }

            // mark the step as done
            $progress->steps->markDone($step);
            $this->progressRepository->appendSteps($progress->uuid, $progress->steps);
        }

        do {
            $nextStep = $progress->steps?->findNext();
            if ($nextStep === null) {
                return $this->checkRemainingAspects($progress, (int)$languageChoice, $aspectChoice, $feedback);
            }
            try {
                return $this->processStep($progress, $nextStep, (int)$languageChoice, $aspectChoice, $feedback);
            } catch (StepSkippedException) {
                continue;
            }
        } while (true);
    }

    private function initializeProcess(): AssistantResponse
    {
        $model = $this->configurationResolver->getDefaultAssistantModel(self::IDENTIFIER);
        $options = $this->buildTargetLanguageOptionItems();

        if (count($options) >= 2) {
            $feedback = [new OptionsFeedback(key: 'language-choice', text: 'Select a language to process:', options: $options)];
        } elseif (count($options) === 1) {
            $feedback = [new ConfirmationFeedback(
                key: 'language-choice',
                text: $options[0]->getTextAndDetails(),
                accept: new ConfirmationItem($options[0]->identifier, 'Start'),
                decline: new ConfirmationItem('', 'Cancel'),
            )];
        } else {
            $feedback = [new TextFeedback('All image files are already fully classified.')];
        }

        return new AssistantResponse(feedback: $feedback, model: (string)$model);
    }

    private function showAspectOptions(int $languageId, Progress $progress): AssistantResponse
    {
        $options = $this->buildAspectOptionItems($languageId);
        if ($options === []) {
            return $this->offerNewLanguage([new TextFeedback('All image files are already fully classified for this language.')]);
        }
        return new AssistantResponse(
            feedback: [new QuickActionFeedback(
                key: 'aspect-choice',
                text: 'What would you like to classify?',
                items: array_map(
                    static fn(OptionItem $o): QuickActionItem => new QuickActionItem($o->identifier, $o->getTextAndDetails()),
                    $options,
                ),
            )],
            progress: $progress,
        );
    }

    private function checkRemainingAspects(Progress $progress, int $languageChoice, string $currentAspect, array $feedback): AssistantResponse
    {
        $remainingOptions = array_values(array_filter(
            $this->buildAspectOptionItems($languageChoice),
            static fn(OptionItem $o): bool => $o->identifier !== $currentAspect,
        ));

        if ($remainingOptions !== []) {
            $aspectOptions = count($remainingOptions) >= 2
                ? new OptionsFeedback(key: 'aspect-choice', text: 'Would you like to focus on another aspect?', options: $remainingOptions)
                : new ConfirmationFeedback(
                    key: 'aspect-choice',
                    text: $remainingOptions[0]->getTextAndDetails(),
                    accept: new ConfirmationItem($remainingOptions[0]->identifier, 'Continue'),
                    decline: new ConfirmationItem('', 'Done'),
                );
            return new AssistantResponse(
                feedback: [...$feedback, new TextFeedback('Done with this aspect!'), $aspectOptions],
                steps: $progress->steps,
                progress: $progress,
            );
        }

        return $this->offerNewLanguage([...$feedback, new TextFeedback('All done for this language!')]);
    }

    private function offerNewLanguage(array $leadingFeedback): AssistantResponse
    {
        $model = $this->configurationResolver->getDefaultAssistantModel(self::IDENTIFIER);
        $options = $this->buildTargetLanguageOptionItems();
        if ($options === []) {
            return new AssistantResponse(
                feedback: [...$leadingFeedback, new TextFeedback('All image files across all languages are now fully classified.')],
                model: (string)$model,
            );
        }
        if (count($options) >= 2) {
            $languageFeedback = [new OptionsFeedback(key: 'language-choice', text: 'Would you like to continue with another language?', options: $options)];
        } else {
            $languageFeedback = [new ConfirmationFeedback(
                key: 'language-choice',
                text: $options[0]->getTextAndDetails(),
                accept: new ConfirmationItem($options[0]->identifier, 'Continue'),
                decline: new ConfirmationItem('', 'Done'),
            )];
        }
        // progress: null signals the client to start a fresh chat session
        return new AssistantResponse(
            feedback: [...$leadingFeedback, ...$languageFeedback],
            model: (string)$model,
        );
    }

    private function processStep(Progress $progress, Step $step, int $languageChoice, string $aspect, array $feedback = []): AssistantResponse
    {
        $model = $progress->model;

        $languageLabel = $this->resolveLanguageLabel($languageChoice);
        $file = $this->resourceFactory->getFileObject((int)$step->identifier);
        $metadataStructure = $this->createMetadataStructure($aspect);

        $metaDataQueryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_file_metadata');
        $currentMetadata = $metaDataQueryBuilder
            ->select('title', 'description', 'alternative')
            ->from('sys_file_metadata')
            ->where(
                $metaDataQueryBuilder->expr()->eq('file', $metaDataQueryBuilder->createNamedParameter((int)$step->identifier, Connection::PARAM_INT)),
                $metaDataQueryBuilder->expr()->eq('sys_language_uid', $metaDataQueryBuilder->createNamedParameter($languageChoice, Connection::PARAM_INT)),
            )
            ->executeQuery()
            ->fetchAssociative();

        $responseType = OptionsAggregate::of(
            IntersectionAggregate::of($metadataStructure)
        );

        $currentTitle = (string)($currentMetadata['title'] ?? '');
        $currentDescription = (string)($currentMetadata['description'] ?? '');
        $currentAlt = (string)($currentMetadata['alternative'] ?? '');
        $aspectLabel = self::ASPECT_LABELS[$aspect];

        $messages = [
            Message::forSystem(implode("\n", [
                'You are a friendly and helpful assistant in the TYPO3 CMS backend.',
                'This is a specific assistant agent that receives image data and generates ',
                'metadata in the requested language. Create three different suggestions.',
                $this->getBackendUserLanguageHint(),
                '',
                'Your entire response must be a single raw JSON object — no markdown, no explanation.',
                'The JSON must conform to this schema:',
                $responseType,
                'Do not return the schema itself. Return actual metadata content that matches the schema.',
            ])),
            Message::ofUser(
                implode("\n", [
                    sprintf('Classify image metadata for language "%s" for the following image.', $languageLabel),
                    sprintf('Focus on the field: %s.', $aspectLabel),
                    'Current values (for context):',
                    sprintf('- Title: %s', $currentTitle !== '' ? $currentTitle : '(empty)'),
                    sprintf('- Description: %s', $currentDescription !== '' ? $currentDescription : '(empty)'),
                    sprintf('- Alt text: %s', $currentAlt !== '' ? $currentAlt : '(empty)'),
                ]),
                new Image($file->getContents(), $file->getMimeType()),
            ),
        ];

        $input = new AgentInput($model, new MessageBag(...$messages));
        // @todo step-progress probably needs to be stored differently (internal, to avoid repeating all messages)
        // $input->progress = $progress;
        // $input->sequencePointer = new SequencePointer(submitted: 1);
        $output = new AgentOutput();

        $this->orchestrator->process($this, $input, $output);

        $optionItems = [];
        foreach ($output->getResultBag()->getResults() as $result) {
            if (!$result instanceof TextResult) {
                continue;
            }
            $content = trim($result->getContent());
            if (str_starts_with($content, '```')) {
                $content = preg_replace('/^```[a-z]*\n?/i', '', $content);
                $content = rtrim($content, '`');
                $content = trim($content);
            }
            $json = json_decode($content, true);
            if (!is_array($json) || ($json['type'] ?? null) !== 'options' || !is_array($json['items'] ?? null)) {
                continue;
            }
            foreach ($json['items'] as $rawItem) {
                $value = $rawItem['value'] ?? [];
                $aspectValue = (string)($value[$aspect] ?? '');
                if ($aspectValue === '') {
                    continue;
                }
                $optionItems[] = new OptionItem(
                    identifier: json_encode($rawItem, JSON_THROW_ON_ERROR),
                    text: $aspectValue,
                    details: '',
                );
            }
        }

        if ($optionItems === []) {
            return new AssistantResponse(
                feedback: [
                    ...$feedback,
                    new TextFeedback('AI response did not contain any metadata suggestions.'),
                    new MediaFeedback($file->getPublicUrl()),
                ],
                stepIndex: $progress->steps?->index($step),
                boomerang: true,
            );
        }

        $optionItems[] = new OptionItem(
            identifier: '',
            text: 'Skip',
            details: 'Skip this item and continue with the next one.',
        );

        $currentValue = (string)($currentMetadata[$aspect] ?? '');
        $currentValueText = $currentValue !== '' ? $currentValue : '_(empty)_';

        return new AssistantResponse(
            feedback: [
                ...$feedback,
                new TextFeedback(sprintf('Select a %s suggestion to apply:', $aspectLabel)),
                new OptionsFeedback(
                    key: 'step-choice',
                    text: sprintf('Current value: %s', $currentValueText),
                    options: $optionItems,
                    heading: $file->getName(),
                    image: $file->getPublicUrl(),
                ),
            ],
            stepIndex: $progress->steps?->index($step),
        );
    }

    private function createProgress(): Progress
    {
        $model = $this->configurationResolver->getDefaultAssistantModel(self::IDENTIFIER);
        $progress = new Progress(
            uuid: Uuid::v4(),
            model: $model,
            initiator: new Initiator(type: 'assistant', subject: self::IDENTIFIER),
            userId: $this->getBackendUserId(),
        );
        $this->progressRepository->add($progress);
        return $progress;
    }

    /**
     * @return list<int>
     */
    private function getAccessibleStorageUids(): array
    {
        // getFileStorages() never returns storage 0 (the pseudo-storage); admin users
        // get all storages, regular users only those they have file mounts for.
        return array_keys($this->getBackendUser()->getFileStorages());
    }

    /**
     * @return list<OptionItem>
     */
    private function buildTargetLanguageOptionItems(): array
    {
        $storageUids = $this->getAccessibleStorageUids();
        if ($storageUids === []) {
            return [];
        }

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
                $queryBuilder->expr()->in(
                    'sf.storage',
                    $queryBuilder->createNamedParameter($storageUids, Connection::PARAM_INT_ARRAY),
                ),
                $queryBuilder->expr()->eq(
                    'sf.type',
                    $queryBuilder->createNamedParameter(FileType::IMAGE->value, Connection::PARAM_INT),
                ),
                $queryBuilder->expr()->in(
                    'sf.mime_type',
                    $queryBuilder->createNamedParameter(self::MIME_TYPES, Connection::PARAM_STR_ARRAY),
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

        $options = [];
        foreach ($rows as $row) {
            $missingCount = (int)$row['missing_count'];
            $languageId = (int)$row['sys_language_uid'];
            $languageLabel = $this->resolveLanguageLabel($languageId);
            $languageIsoCode = $this->resolveLanguageIsoCode($languageId);
            $options[] = new OptionItem(
                identifier: (string)$languageId,
                text: sprintf('Process language %s', $languageLabel),
                details: sprintf(
                    '%s%d %s',
                    $languageIsoCode !== null ? $languageIsoCode . ', ' : '',
                    $missingCount,
                    $missingCount === 1 ? 'file' : 'files'
                ),
            );
        }

        return $options;
    }

    /**
     * @return list<OptionItem>
     */
    private function buildAspectOptionItems(int $languageId): array
    {
        $storageUids = $this->getAccessibleStorageUids();
        if ($storageUids === []) {
            return [];
        }

        $options = [];
        foreach (self::ASPECT_LABELS as $field => $label) {
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_file_metadata');
            $queryBuilder->getRestrictions()->removeAll();

            $count = (int)$queryBuilder
                ->addSelectLiteral('COUNT(DISTINCT sfm.file) AS missing_count')
                ->from('sys_file_metadata', 'sfm')
                ->innerJoin(
                    'sfm',
                    'sys_file',
                    'sf',
                    $queryBuilder->expr()->eq('sf.uid', $queryBuilder->quoteIdentifier('sfm.file')),
                )
                ->where(
                    $queryBuilder->expr()->in(
                        'sf.storage',
                        $queryBuilder->createNamedParameter($storageUids, Connection::PARAM_INT_ARRAY),
                    ),
                    $queryBuilder->expr()->eq(
                        'sf.type',
                        $queryBuilder->createNamedParameter(FileType::IMAGE->value, Connection::PARAM_INT),
                    ),
                    $queryBuilder->expr()->in(
                        'sf.mime_type',
                        $queryBuilder->createNamedParameter(self::MIME_TYPES, Connection::PARAM_STR_ARRAY),
                    ),
                    $queryBuilder->expr()->eq(
                        'sfm.sys_language_uid',
                        $queryBuilder->createNamedParameter($languageId, Connection::PARAM_INT),
                    ),
                    $queryBuilder->expr()->or(
                        $queryBuilder->expr()->isNull('sfm.' . $field),
                        $queryBuilder->expr()->eq('sfm.' . $field, $queryBuilder->createNamedParameter('')),
                    ),
                )
                ->executeQuery()
                ->fetchOne();

            if ($count > 0) {
                $options[] = new OptionItem(
                    identifier: $field,
                    text: sprintf('Focus on %s', $label),
                    details: sprintf('%d %s', $count, $count === 1 ? 'file' : 'files'),
                );
            }
        }

        return $options;
    }

    private function resolveLanguageLabel(int $languageId, int $pageId = 0): string
    {
        $languages = $this->translationConfigurationProvider->getSystemLanguages($pageId);
        $language = $languages[$languageId] ?? null;
        return $languageId === 0 ? 'Default' : ($language['title'] ?? 'Language ' . $languageId);
    }

    private function resolveLanguageIsoCode(int $languageId, int $pageId = 0): ?string
    {
        $languages = $this->translationConfigurationProvider->getSystemLanguages($pageId);
        $language = $languages[$languageId] ?? null;
        return $language['ISOcode'] ?? null;
    }

    private function buildSteps(int $targetLanguage, string $aspect): StepCollection
    {
        $storageUids = $this->getAccessibleStorageUids();
        if ($storageUids === []) {
            return new StepCollection([]);
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_file_metadata');
        $queryBuilder->getRestrictions()->removeAll();

        $rows = $queryBuilder
            ->select('sf.uid', 'sf.identifier')
            ->from('sys_file_metadata', 'sfm')
            ->innerJoin(
                'sfm',
                'sys_file',
                'sf',
                $queryBuilder->expr()->eq('sf.uid', $queryBuilder->quoteIdentifier('sfm.file')),
            )
            ->where(
                $queryBuilder->expr()->in(
                    'sf.storage',
                    $queryBuilder->createNamedParameter($storageUids, Connection::PARAM_INT_ARRAY),
                ),
                $queryBuilder->expr()->eq(
                    'sf.type',
                    $queryBuilder->createNamedParameter(FileType::IMAGE->value, Connection::PARAM_INT),
                ),
                $queryBuilder->expr()->in(
                    'sf.mime_type',
                    $queryBuilder->createNamedParameter(self::MIME_TYPES, Connection::PARAM_STR_ARRAY),
                ),
                $queryBuilder->expr()->eq(
                    'sfm.sys_language_uid',
                    $queryBuilder->createNamedParameter($targetLanguage, Connection::PARAM_INT),
                ),
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->isNull('sfm.' . $aspect),
                    $queryBuilder->expr()->eq('sfm.' . $aspect, $queryBuilder->createNamedParameter('')),
                ),
            )
            ->orderBy('sf.identifier', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();

        return new StepCollection(array_map(
            static fn(array $row): Step => new Step(
                identifier: (string)$row['uid'],
                description: (string)$row['identifier'],
                subject: 'sys_file:' . $row['uid'],
            ),
            $rows,
        ));
    }

    /**
     * @todo ugly and hardcoded, should use a generic subject pointer for resolving resources
     */
    private function createMetadataStructure(?string $aspect = null): StructureAggregate
    {
        $props = [
            'title' => new PropertyDefinition(name: 'title', comment: 'Description used in title attribute in markup for this image.'),
            'description' => new PropertyDefinition(name: 'description', comment: 'Detailed description used as potential caption below the image.'),
            'alternative' => new PropertyDefinition(name: 'alternative', comment: 'Alternative description used as substitute in a web accessibility context.'),
        ];

        $properties = $aspect !== null && array_key_exists($aspect, $props)
            ? [$aspect => $props[$aspect]]
            : $props;

        $tcaSchema = $this->schemaFactory->get('sys_file_metadata');
        return StructureAggregate::fromTcaSchema($tcaSchema, ...$properties);
    }

    /**
     * Validates that $json is a well-formed payload for the given $type.
     * Returns the decoded array on success, or null on any validation failure.
     */
    private function validateStepChoice(AggregateInterface|TypeInterface $type, string $json): ?array
    {
        $decoded = json_decode($json, true);
        $schema = $type->toJsonSchema()->data;
        $discriminator = $schema['properties']['type']['const'] ?? null;

        if (!is_array($decoded) || ($decoded['type'] ?? null) !== $discriminator) {
            return null;
        }

        $valueSchema = $schema['properties']['value'] ?? [];
        if (($valueSchema['type'] ?? null) !== 'object') {
            return $decoded;
        }

        if (!is_array($decoded['value'] ?? null)) {
            return null;
        }

        $valueProps = $valueSchema['properties'] ?? [];
        $value = $decoded['value'];

        foreach ($value as $key => $val) {
            if (!array_key_exists($key, $valueProps)) {
                return null;
            }
            $expectedType = $valueProps[$key]['type'] ?? 'string';
            $valid = match ($expectedType) {
                'boolean' => is_bool($val),
                'integer' => is_int($val),
                'number'  => is_int($val) || is_float($val),
                default   => is_string($val),
            };
            if (!$valid) {
                return null;
            }
        }

        return $decoded;
    }
}
