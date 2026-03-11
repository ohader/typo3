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
use Symfony\AI\Platform\Result\ResultInterface;
use Symfony\Component\Uid\Uuid;
use TYPO3\CMS\Assist\AI\Agent\AgentCallRequest;
use TYPO3\CMS\Assist\AI\Agent\SequencePointer;
use TYPO3\CMS\Assist\AI\Message\AgentInput;
use TYPO3\CMS\Assist\AI\Message\AgentInputInterface;
use TYPO3\CMS\Assist\AI\Message\AgentOutput;
use TYPO3\CMS\Assist\AI\Message\AgentOutputInterface;
use TYPO3\CMS\Assist\AI\Tool\FetchPageRecords;
use TYPO3\CMS\Assist\Attribute\AsAssistant;
use TYPO3\CMS\Assist\Domain\Enum\AssistantCapability;
use TYPO3\CMS\Assist\Domain\Enum\ProgressItemType;
use TYPO3\CMS\Assist\Domain\Model\Initiator;
use TYPO3\CMS\Assist\Domain\Model\Progress;
use TYPO3\CMS\Assist\Domain\Model\ProgressItem;
use TYPO3\CMS\Assist\Domain\Repository\ProgressRepository;
use TYPO3\CMS\Assist\Service\AssistantRegistry;
use TYPO3\CMS\Assist\Service\ConfigurationResolver;

#[AsAssistant(
    identifier: self::IDENTIFIER,
    capabilities: [AssistantCapability::messages, AssistantCapability::toolCalling],
    triggerResources: ['pages', 'tt_content'],
    triggerRoutes: ['/module/web/layout'],
    labelDomain: 'assist.assistants.inline_chat',
)]
final readonly class InlineChatAssistant implements AssistantInterface
{
    private const IDENTIFIER = 'typo3-assist-inline-chat';

    use AssistantContextTrait;

    public function __construct(
        private ProgressRepository $progressRepository,
        private AssistantRegistry $assistantRegistry,
        private AssistantOrchestrator $orchestrator,
        private ConfigurationResolver $configurationResolver,
        private Feedback\ResultConverter $resultConverter,
    ) {}

    public function getSystemPrompt(): ?string
    {
        return implode("\n", [
            'You are a friendly and helpful assistant in the TYPO3 CMS backend.',
            'Your role is to answer TYPO3-related questions and help users with TYPO3 tasks.',
            'Always be polite, concise, and focused on TYPO3 topics.',
            $this->getBackendUserLanguageHint(),
            '',
            'Respond only in JSON matching this schema:',
            Type\UnionAggregate::of(
                Type\MarkdownType::class,
                Type\TextType::class,
                Type\ListAggregate::of(Type\TextType::class),
                Type\OptionsAggregate::of(Type\MarkdownType::class),
                Type\OptionsAggregate::of(Type\TextType::class),
            ),
        ]);
    }

    public function getToolPolicy(): ToolPolicy
    {
        return new StaticToolPolicy([FetchPageRecords::class]);
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

        if (isset($request->params['message']) && $request->params['message'] !== '') {
            return $this->startConversation($request);
        }

        return $this->greet();
    }

    private function greet(): AssistantResponse
    {
        return new AssistantResponse(
            feedback: [new Feedback\TextFeedback('Hello! How are you? How can I help you with TYPO3 today?')],
        );
    }

    private function startConversation(AssistantRequest $request): AssistantResponse
    {
        $model = $this->configurationResolver->getDefaultAssistantModel('typo3-assist-inline-chat');
        if ($model === null) {
            return new AssistantResponse(feedback: [new Feedback\TextFeedback('No model configured for this assistant.')]);
        }

        $message = (string)$request->params['message'];

        $progress = new Progress(
            uuid: Uuid::v4(),
            model: $model,
            initiator: new Initiator(type: 'assistant', subject: self::IDENTIFIER),
            userId: $this->getBackendUserId(),
            items: [new ProgressItem(ProgressItemType::submitted, $message)],
        );
        $this->progressRepository->add($progress);

        $input = new AgentInput($model, Message::ofUser($message));
        $input->progress = $progress;
        $input->sequencePointer = new SequencePointer(submitted: 1);
        $output = new AgentOutput();

        $assistant = $this->assistantRegistry->getAssistant(self::IDENTIFIER);
        $this->orchestrator->process($assistant, $input, $output);

        $feedback = $this->parseFeedback($output);

        return new AssistantResponse(feedback: $feedback, progress: $progress, model: (string)$model);
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

        $feedback = $this->parseFeedback($output);

        return new AssistantResponse(feedback: $feedback, progress: $progress);
    }

    /**
     * @return list<Feedback\FeedbackInterface>
     */
    private function parseFeedback(AgentOutputInterface $output): array
    {
        $feedback = [];
        foreach ($output->getResultBag()->getResults() as $result) {
            $feedback[] = $this->convertResult($result);
        }
        return $feedback;
    }

    private function convertResult(ResultInterface $result): Feedback\FeedbackInterface
    {
        return $this->resultConverter->convert($result);
    }
}
