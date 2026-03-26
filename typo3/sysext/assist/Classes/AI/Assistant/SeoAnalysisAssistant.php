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
use TYPO3\CMS\Assist\AI\Assistant\Type\MarkdownType;
use TYPO3\CMS\Assist\AI\Message\AgentInput;
use TYPO3\CMS\Assist\AI\Message\AgentOutput;
use TYPO3\CMS\Assist\AI\Tool\FetchContentElementsTool;
use TYPO3\CMS\Assist\AI\Tool\FetchPageRecordsTool;
use TYPO3\CMS\Assist\AI\Tool\PerformFrontendRequestTool;
use TYPO3\CMS\Assist\Attribute\AsAssistant;
use TYPO3\CMS\Assist\Domain\Dto\PageSubject;
use TYPO3\CMS\Assist\Domain\Enum\AssistantCapability;
use TYPO3\CMS\Assist\Domain\Enum\ChatInputType;
use TYPO3\CMS\Assist\Domain\Enum\ProgressItemType;
use TYPO3\CMS\Assist\Domain\Model\Assistant;
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
    capabilities: [AssistantCapability::messages, AssistantCapability::toolCalling],
    triggerResources: ['pages', 'tt_content'],
    triggerRoutes: ['/module/web/layout'],
    labelDomain: 'assist.assistants.seo_analysis',
    chatInput: ChatInputType::optional,
)]
final readonly class SeoAnalysisAssistant implements AssistantInterface
{
    use AssistantContextTrait;

    private const IDENTIFIER = 'typo3-assist-seo-analysis';

    public function __construct(
        private ProgressRepository $progressRepository,
        private AssistantRegistry $assistantRegistry,
        private AssistantOrchestrator $orchestrator,
        private ConfigurationResolver $configurationResolver,
        private Feedback\ResultConverter $resultConverter,
    ) {}

    public function getAssistant(): Assistant
    {
        return $this->assistantRegistry->getAssistant(self::IDENTIFIER);
    }

    public function getSystemPrompt(): ?string
    {
        return implode("\n", [
            'You are an SEO expert assistant in the TYPO3 CMS backend.',
            'Analyze TYPO3 page content for SEO opportunities and provide actionable recommendations.',
            '',
            'Evaluate the following when analyzing a page:',
            '- Page title (50–60 chars, primary keyword near the front)',
            '- Meta description (150–160 chars, primary keyword, compelling CTA)',
            '- Keywords field (relevant, not keyword-stuffed)',
            '- Content headings (keyword variations, logical hierarchy)',
            '- Body text (keyword density 1–3%, secondary keywords, internal linking opportunities)',
            '',
            'If the user provides a focus keyphrase, prioritize the analysis for that keyphrase.',
            '',
            'Available tools:',
            '- typo3-assist-fetchPages: fetches page records (title, description, keywords, abstract)',
            '- typo3-assist-fetchContentElements: fetches raw content elements (header, bodytext) for a page UID',
            '- typo3-assist-performFrontendRequest: renders the full frontend HTML of a page by UID (use to inspect meta tags and rendered output)',
            '',
            $this->getBackendUserLanguageHint(),
            'Respond with a markdown table. Each row represents one SEO criterion (e.g. Page title, Meta description, Keywords, Headings, Body text).',
            'Columns: Criterion | Current value | Status | Recommendation. Use ✅ / ⚠️ / ❌ for the status column.',
            'Add specific, actionable recommendations in the last column.',
            'Respond with JSON, using the following schema:',
            (string)MarkdownType::toJsonSchema(),
        ]);
    }

    public function getToolPolicy(): ToolPolicy
    {
        return new StaticToolPolicy([FetchPageRecordsTool::class, FetchContentElementsTool::class, PerformFrontendRequestTool::class]);
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

    public function handleClientRequest(AssistantRequest $request): AssistantResponse
    {
        $progressUuid = $request->getProgressUuid();

        if ($progressUuid !== null) {
            return $this->continueProgress($progressUuid, $request);
        }

        $mode = $request->params['seo-analysis-mode'] ?? null;

        if ($mode === 'generic') {
            return $this->startConversation($request, $this->buildAnalysisMessage($request, null));
        }

        if ($mode === 'keyword') {
            return new AssistantResponse(
                feedback: [new Feedback\TextFeedback('What keyword would you like to focus on?')],
                showInput: true,
            );
        }

        $message = (string)($request->params['message'] ?? '');
        if ($message !== '') {
            return $this->startConversation($request, $this->buildAnalysisMessage($request, $message));
        }

        return $this->greet($request);
    }

    private function greet(AssistantRequest $request): AssistantResponse
    {
        $subject = $request->getSubject();
        $pageInfo = $subject instanceof PageSubject ? ' for page ' . $subject->uid : '';

        return new AssistantResponse(
            feedback: [new Feedback\QuickActionFeedback(
                key: 'seo-analysis-mode',
                text: 'How would you like to analyse the page' . $pageInfo . '?',
                items: [
                    new Feedback\QuickActionItem('generic', 'Generic summary'),
                    new Feedback\QuickActionItem('keyword', 'Specific keyword analysis'),
                ],
            )],
        );
    }

    private function buildAnalysisMessage(AssistantRequest $request, ?string $keyword): string
    {
        $subject = $request->getSubject();
        $pageUid = $subject instanceof PageSubject ? $subject->uid : null;

        if ($keyword !== null) {
            return $pageUid !== null
                ? "Analyse page {$pageUid} for focus keyphrase: {$keyword}"
                : "Analyse for focus keyphrase: {$keyword}";
        }
        return $pageUid !== null ? "Analyse page {$pageUid}" : 'Analyse this page for SEO';
    }

    private function startConversation(AssistantRequest $request, string $message): AssistantResponse
    {
        $model = $this->configurationResolver->getDefaultAssistantModel(self::IDENTIFIER);
        if ($model === null) {
            return new AssistantResponse(feedback: [new Feedback\TextFeedback('No model configured for this assistant.')]);
        }

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

        try {
            $this->orchestrator->process($this, $input, $output, $request->params);
        } catch (ToolApprovalRequiredException $e) {
            return $this->buildToolApprovalResponse($e, $input->progress, $request->params);
        }

        $feedback = $this->parseFeedback($output);

        return new AssistantResponse(
            feedback: $feedback,
            progress: $progress,
            model: (string)$model,
            showInput: false,
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

        try {
            $this->orchestrator->process($this, $input, $output, $request->params);
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
            $feedback[] = $this->resultConverter->convert($result);
        }
        return $feedback;
    }
}
