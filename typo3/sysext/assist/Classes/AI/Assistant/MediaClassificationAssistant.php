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
use Symfony\AI\Platform\Result\TextResult;
use Symfony\Component\Uid\Uuid;
use TYPO3\CMS\Assist\AI\Agent\AgentCallRequest;
use TYPO3\CMS\Assist\AI\Agent\SequencePointer;
use TYPO3\CMS\Assist\AI\Assistant\Feedback\ConfirmationFeedback;
use TYPO3\CMS\Assist\AI\Assistant\Feedback\ConfirmationItem;
use TYPO3\CMS\Assist\AI\Assistant\Feedback\OptionItem;
use TYPO3\CMS\Assist\AI\Assistant\Feedback\OptionsFeedback;
use TYPO3\CMS\Assist\AI\Assistant\Feedback\ResultConverter;
use TYPO3\CMS\Assist\AI\Assistant\Feedback\TextFeedback;
use TYPO3\CMS\Assist\AI\Assistant\Type\PropertyDefinition;
use TYPO3\CMS\Assist\AI\Assistant\Type\StructureAggregate;
use TYPO3\CMS\Assist\AI\Message\AgentInput;
use TYPO3\CMS\Assist\AI\Message\AgentInputInterface;
use TYPO3\CMS\Assist\AI\Message\AgentOutput;
use TYPO3\CMS\Assist\AI\Message\AgentOutputInterface;
use TYPO3\CMS\Assist\Attribute\AsAssistant;
use TYPO3\CMS\Assist\Domain\Enum\AssistantCapability;
use TYPO3\CMS\Assist\Domain\Enum\ProgressItemType;
use TYPO3\CMS\Assist\Domain\Model\Initiator;
use TYPO3\CMS\Assist\Domain\Model\StateCollection;
use TYPO3\CMS\Assist\Domain\Model\Progress;
use TYPO3\CMS\Assist\Domain\Model\ProgressItem;
use TYPO3\CMS\Assist\Domain\Model\Step;
use TYPO3\CMS\Assist\Domain\Model\StepCollection;
use TYPO3\CMS\Assist\Domain\Repository\ProgressRepository;
use TYPO3\CMS\Assist\Exception\StepSkippedException;
use TYPO3\CMS\Assist\Service\AssistantRegistry;
use TYPO3\CMS\Assist\Service\ConfigurationResolver;
use TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\FileType;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
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

    public function getSystemPrompt(): ?string
    {
        return null;
        return implode("\n", [
            'You are a TYPO3 CMS media classification assistant.',
            'Your task is to analyse images and generate metadata in the requested language.',
            'For each image produce a title (short), description (one sentence), and alternative text (screen-reader caption).',
            '',
            'Respond only in JSON matching this schema:',
            Type\UnionAggregate::of(
                Type\TextType::class,
                Type\ListAggregate::of(Type\StructureAggregate::fromDefinition(
                    new Type\PropertyDefinition('title', 'string', 'Short title for the media file'),
                    new Type\PropertyDefinition('description', 'string', 'One-sentence description'),
                    new Type\PropertyDefinition('alternative', 'string', 'Screen-reader alternative text'),
                )),
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
        $stepIndex = $request->getStepIndex();

        $languageChoice = $request->params['language-choice'] ?? null;
        $stepChoice = $request->params['step-choice'] ?? null;

        if ($languageChoice === '') {
            return new AssistantResponse(feedback: [new TextFeedback("Cancelled. We're done here...")]);
        }

        // raise questions about what to do actually
        if ($progressUuid === null && $languageChoice === null) {
            return $this->initializeProcess();
        }

        // initialize progress and steps
        if ($progressUuid === null && MathUtility::canBeInterpretedAsInteger($languageChoice)) {
            $progress = $this->createProgress();
            $steps = $this->buildSteps((int)$languageChoice);
            // @todo steps persistance should be handled more generic in an upper layer
            $this->progressRepository->appendSteps($progress->uuid, $steps);
            $this->progressRepository->appendState(
                $progress->uuid,
                new StateCollection(['language-choice' => $languageChoice]),
            );
            return new AssistantResponse(
                feedback: [new TextFeedback("Alright, let's get started...")],
                steps: $steps,
                progress: $progress,
                state: new StateCollection(['language-choice' => $languageChoice]),
                boomerang: true,
            );
        }

        if (!MathUtility::canBeInterpretedAsInteger($languageChoice)) {
            return new AssistantResponse(error: 'Unexpected state: No language choice submitted.');
        }
        if ($progressUuid === null) {
            return new AssistantResponse(error: 'Unexpected state: No progress header submitted.');
        }
        $progress = $this->progressRepository->findByUuid($progressUuid);

        if ($stepIndex !== null && $stepChoice === null) {
            return new AssistantResponse(error: 'Unexpected state: No step choice submitted.');
        }

        if ($stepChoice !== null) {
            // @todo apply step choice
        }

        do {
            $nextStep = $progress->steps?->findNext();
            if ($nextStep === null) {
                return new AssistantResponse(feedback: [new TextFeedback('All done!')]);
            }
            try {
                return $this->processStep($progress, $nextStep, (int)$languageChoice);
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

    private function processStep(Progress $progress, Step $step, int $languageChoice): AssistantResponse
    {
        // @todo handle unassigned models much earlier in the call stack
        $model = $this->configurationResolver->getDefaultAssistantModel(self::IDENTIFIER);

        $languages = $this->translationConfigurationProvider->getSystemLanguages(0);
        $language = $languages[$languageChoice] ?? null;
        $languageLabel = $languageChoice === 0 ? 'Default' : ($language['title'] ?? 'Language ' . $languageChoice);

        // @todo ugly and hardcoded, should use a generic subject pointer for resolving resources
        $file = $this->resourceFactory->getFileObject((int)$step->identifier);
        $values = [
            'title' => $file->getProperty('title'),
            'description' => $file->getProperty('description'),
            'alternative' => $file->getProperty('alternative'),
        ];
        $props = [
            'title' => new PropertyDefinition(name: 'title', comment: 'Description used in title attribute in markup for this image.'),
            'description' => new PropertyDefinition(name: 'description', comment: 'Detailed description used as potential caption below the image.'),
            'alternative' => new PropertyDefinition(name: 'description', comment: 'Alternative description used as substitute in a web accessibility context.'),
        ];
        $emptyProperties = array_filter($values, static fn(?string $value): bool => $value === null || $value === '');
        $tcaSchema = $this->schemaFactory->get('sys_file');

        $responseType = \TYPO3\CMS\Assist\AI\Assistant\Type\OptionsAggregate::of(
            \TYPO3\CMS\Assist\AI\Assistant\Type\IntersectionAggregate::of(
                StructureAggregate::fromTcaSchema($tcaSchema, ...array_intersect_key($props, $emptyProperties)),
            )
        );

        $messages = [
            Message::forSystem(implode("\n", [
                'You are a friendly and helpful assistant in the TYPO3 CMS backend.',
                'This is a specific assistant agent that receives image data and generates ',
                'metadata in the requested language. Create three different suggestions.',
                $this->getBackendUserLanguageHint(),
                '',
                'Respond only in JSON matching this schema:',
                $responseType,
            ])),
            Message::ofUser(
                sprintf(
                    'Classify image metadata (%s) for language "%s" for the following image:',
                    implode(', ', array_keys($emptyProperties)),
                    $languageLabel,
                ),
                new Image($file->getContents(), $file->getMimeType()),
            ),
        ];

        $input = new AgentInput($model, ...$messages);
        // @todo step-progress probably needs to be stored differently (internal, to avoid repeating all messages)
        // $input->progress = $progress;
        // $input->sequencePointer = new SequencePointer(submitted: 1);
        $output = new AgentOutput();

        $assistant = $this->assistantRegistry->getAssistant(self::IDENTIFIER);
        $this->orchestrator->process($assistant, $input, $output);

        $optionItems = [];
        foreach ($output->getResultBag()->getResults() as $result) {
            if (!$result instanceof TextResult) {
                continue;
            }
            $json = json_decode($result->getContent(), true);
            if (!is_array($json) || ($json['type'] ?? null) !== 'options' || !is_array($json['items'] ?? null)) {
                continue;
            }
            foreach ($json['items'] as $rawItem) {
                $value = $rawItem['value'] ?? [];
                $lines = [];
                foreach ($value as $fieldName => $fieldValue) {
                    $lines[] = sprintf('**%s:** %s', ucfirst((string)$fieldName), $fieldValue);
                }
                $optionItems[] = new OptionItem(
                    identifier: json_encode($rawItem, JSON_THROW_ON_ERROR),
                    text: reset($value) ?? '',
                    details: implode("  \n", $lines),
                );
            }
        }

        if ($optionItems === []) {
            throw new StepSkippedException('Skipped', 1773749903);
        }

        $feedback = [
            // @todo create a MediaFeedback item that is capable of holding an image or a video
            new TextFeedback($file->getCombinedIdentifier() . ' (should be an image)'),
        ];
        $feedback[] = new OptionsFeedback(
            key: 'step-choice',
            text: 'Select a metadata suggestion to apply:',
            options: $optionItems,
        );

        return new AssistantResponse(feedback: $feedback, stepIndex: $progress->steps?->index($step));
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
     * @return list<OptionItem>
     */
    private function buildTargetLanguageOptionItems(): array
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
                text: sprintf('Process language %s', $label),
                details: sprintf(
                    '%s%d %s',
                    !empty($language['ISOcode']) ? $language['ISOcode'] . ', ' : '',
                    $count,
                    $count === 1 ? 'file' : 'files'
                ),
            );
        }

        return $options;
    }

    private function buildSteps(int $targetLanguage): StepCollection
    {
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
                    $queryBuilder->expr()->isNull('sfm.title'),
                    $queryBuilder->expr()->eq('sfm.title', $queryBuilder->createNamedParameter('')),
                    $queryBuilder->expr()->isNull('sfm.description'),
                    $queryBuilder->expr()->eq('sfm.description', $queryBuilder->createNamedParameter('')),
                    $queryBuilder->expr()->isNull('sfm.alternative'),
                    $queryBuilder->expr()->eq('sfm.alternative', $queryBuilder->createNamedParameter('')),
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
            $this->progressRepository->appendItem(
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

        $assistant = $this->assistantRegistry->getAssistant(self::IDENTIFIER);
        $this->orchestrator->process($assistant, $input, $output);

        $feedback = array_map(
            fn($result) => $this->resultConverter->convert($result),
            $output->getResultBag()->getResults(),
        );

        $steps = StepCollection::fromArray($request->params['steps'] ?? []);
        return new AssistantResponse(steps: $steps, feedback: $feedback, progress: $progress);
    }
}
