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
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Platform\Result\ResultInterface;
use Symfony\Component\Uid\Uuid;
use TYPO3\CMS\Assist\AI\Agent\AgentCallRequest;
use TYPO3\CMS\Assist\AI\Agent\ApprovingToolbox;
use TYPO3\CMS\Assist\AI\Agent\SequencePointer;
use TYPO3\CMS\Assist\AI\Assistant\Feedback\ConfirmationItem;
use TYPO3\CMS\Assist\AI\Assistant\Feedback\ToolApprovalFeedback;
use TYPO3\CMS\Assist\AI\Message\AgentInput;
use TYPO3\CMS\Assist\AI\Message\AgentOutput;
use TYPO3\CMS\Assist\AI\Tool\FetchPageRecordsTool;
use TYPO3\CMS\Assist\Attribute\AsAssistant;
use TYPO3\CMS\Assist\Domain\Enum\AssistantCapability;
use TYPO3\CMS\Assist\Domain\Enum\ChatInputType;
use TYPO3\CMS\Assist\Domain\Enum\ProgressItemType;
use TYPO3\CMS\Assist\Domain\Model\Initiator;
use TYPO3\CMS\Assist\Domain\Model\Progress;
use TYPO3\CMS\Assist\Domain\Model\ProgressItem;
use TYPO3\CMS\Assist\Domain\Model\StateCollection;
use TYPO3\CMS\Assist\Domain\Repository\ProgressRepository;
use TYPO3\CMS\Assist\Exception\ToolApprovalRequiredException;
use TYPO3\CMS\Assist\Service\AssistantRegistry;
use TYPO3\CMS\Assist\Service\ConfigurationResolver;

#[AsAssistant(
    identifier: self::IDENTIFIER,
    capabilities: [AssistantCapability::messages],
    triggerResources: ['pages', 'tt_content'],
    triggerRoutes: ['/module/web/layout'],
    labelDomain: 'assist.assistants.inline_chat',
    chatInput: ChatInputType::visible,
)]
final readonly class InlineChatAssistant implements AssistantInterface
{
    use AssistantContextTrait;

    private const IDENTIFIER = 'typo3-assist-inline-chat';

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

    public function getToolPolicy(): ?ToolPolicy
    {
        return null;
        return new StaticToolPolicy([FetchPageRecordsTool::class]);
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

        $input = new AgentInput($model, new MessageBag(Message::ofUser($message)));
        $input->progress = $progress;
        $input->sequencePointer = new SequencePointer(submitted: 1);
        $output = new AgentOutput();

        $assistant = $this->assistantRegistry->getAssistant(self::IDENTIFIER);
        try {
            $this->orchestrator->process($assistant, $input, $output, $request->params);
        } catch (ToolApprovalRequiredException $e) {
            return $this->buildToolApprovalResponse($e, $input->progress, $request->params);
        }

        $feedback = $this->parseFeedback($output);

        return new AssistantResponse(
            feedback: $feedback,
            progress: $progress,
            model: (string)$model,
            state: $this->buildAlwaysApprovedState($request->params),
        );
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
        $input = new AgentInput($model, new MessageBag(...$messages));
        $input->progress = $progress;
        $input->sequencePointer = new SequencePointer(submitted: count($progress->items));
        $output = new AgentOutput();

        $assistant = $this->assistantRegistry->getAssistant(self::IDENTIFIER);
        try {
            $this->orchestrator->process($assistant, $input, $output, $request->params);
        } catch (ToolApprovalRequiredException $e) {
            return $this->buildToolApprovalResponse($e, $input->progress, $request->params);
        }

        $feedback = $this->parseFeedback($output);

        return new AssistantResponse(
            feedback: $feedback,
            progress: $progress,
            state: $this->buildAlwaysApprovedState($request->params),
        );
    }

    private function buildToolApprovalResponse(
        ToolApprovalRequiredException $e,
        ?Progress $progress,
        array $params,
    ): AssistantResponse {
        return new AssistantResponse(
            feedback: [new ToolApprovalFeedback(
                key: ApprovingToolbox::PARAM_PREFIX . $e->toolName,
                toolName: $e->toolName,
                parameters: $e->parameters,
                approve: new ConfirmationItem('approve', 'Allow'),
                alwaysApprove: new ConfirmationItem('always-approve', 'Always allow'),
                decline: new ConfirmationItem('decline', 'Decline'),
            )],
            progress: $progress,
            state: $this->buildAlwaysApprovedState($params),
        );
    }

    private function buildAlwaysApprovedState(array $params): ?StateCollection
    {
        $prev = json_decode($params['assistToolApprovals'] ?? '[]', true) ?? [];
        $new = [];
        foreach ($params as $key => $value) {
            if (str_starts_with($key, ApprovingToolbox::PARAM_PREFIX) && $value === 'always-approve') {
                $new[] = substr($key, strlen(ApprovingToolbox::PARAM_PREFIX));
            }
        }
        $all = array_values(array_unique([...$prev, ...$new]));
        return $all !== [] ? new StateCollection(['assistToolApprovals' => json_encode($all)]) : null;
    }

    /**
     * @return list<Feedback\FeedbackInterface>
     */
    private function parseFeedback(AgentOutput $output): array
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
