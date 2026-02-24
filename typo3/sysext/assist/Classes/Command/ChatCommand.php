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

namespace TYPO3\CMS\Assist\Command;

use Symfony\AI\Platform\Message\Message;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use TYPO3\CMS\Assist\AI\Assistant\AssistantOrchestrator;
use TYPO3\CMS\Assist\AI\Message\AgentInput;
use TYPO3\CMS\Assist\AI\Message\AgentOutput;
use TYPO3\CMS\Assist\AI\Platform\PlatformModel;
use TYPO3\CMS\Assist\Domain\Enum\Availability;
use TYPO3\CMS\Assist\Service\AssistantRegistry;
use TYPO3\CMS\Assist\Service\ConfigurationResolver;
use TYPO3\CMS\Core\Authentication\CommandLineUserAuthentication;
use TYPO3\CMS\Core\Core\Bootstrap;

/**
 * Interactive AI chat session via the command line.
 */
#[AsCommand('assist:chat', 'Start an interactive AI chat session')]
final class ChatCommand extends Command
{
    use CommandTrait;

    public function __construct(
        private readonly ConfigurationResolver $configurationResolver,
        private readonly AssistantRegistry $assistantRegistry,
        private readonly AssistantOrchestrator $assistantOrchestrator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'assistant',
                InputArgument::OPTIONAL,
                'The assistant identifier (available: ' . implode(', ', array_keys($this->assistantRegistry->getAssistants())) . ')',
                'typo3-assist-inline-chat',
                array_keys($this->assistantRegistry->getAssistants()),
            )
            ->addArgument('model', InputArgument::OPTIONAL, 'The model@platform identifier (see assist:list)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        Bootstrap::initializeBackendUser(CommandLineUserAuthentication::class);
        Bootstrap::initializeBackendAuthentication();

        $assistantIdentifier = $input->getArgument('assistant');
        if (!$this->assistantRegistry->hasAssistant($assistantIdentifier)) {
            $output->writeln(sprintf('<error>Assistant "%s" is not registered.</error>', $assistantIdentifier));
            return Command::FAILURE;
        }
        $assistant = $this->assistantRegistry->getAssistant($assistantIdentifier);

        $modelValue = $input->getArgument('model');
        if ($modelValue !== null) {
            try {
                $platformModel = PlatformModel::fromString($modelValue);
            } catch (\InvalidArgumentException $e) {
                $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
                return Command::FAILURE;
            }
        } else {
            $platformModel = $this->configurationResolver->getDefaultAssistantModel($assistant->identifier);
            if ($platformModel === null) {
                $output->writeln('<error>No model configured for this assistant. Provide a model argument or configure one in the site configuration.</error>');
                return Command::FAILURE;
            }
        }

        $platformFound = false;
        foreach ($this->configurationResolver->getDefaultPlatforms() as $platform) {
            if ($platform->package === $platformModel->platform && $platform->availability === Availability::enabled) {
                $platformFound = true;
                break;
            }
        }
        if (!$platformFound) {
            $output->writeln(sprintf('<error>Platform "%s" is not configured or not enabled.</error>', $platformModel->platform));
            return Command::FAILURE;
        }

        $agentInput = new AgentInput($platformModel);

        /** @var QuestionHelper $questionHelper */
        $questionHelper = $this->getHelper('question');
        $useReadline = function_exists('readline') && $input->isInteractive();

        $this->installSigintHandler($output, 'Chat session ended.');

        $output->writeln(sprintf('AI Chat — <info>%s</info>', $platformModel));
        $output->writeln('Type your message and press Enter. Send an empty message or type <comment>/quit</comment> to exit.');
        $output->writeln('');

        while (true) {
            if ($useReadline) {
                $raw = readline($output->getFormatter()->format('<info>You:</info> '));
                $userText = $raw === false ? null : $raw;
            } else {
                $userText = $questionHelper->ask($input, $output, new Question('<info>You:</info> '));
            }

            if ($userText === null || $userText === '' || $userText === '/quit') {
                break;
            }

            if ($useReadline) {
                readline_add_history($userText);
            }

            $agentInput->add(Message::ofUser($userText));
            $agentOutput = new AgentOutput();

            try {
                $this->withSpinner($output, 'Thinking…', function () use ($assistant, $agentInput, $agentOutput): void {
                    $this->assistantOrchestrator->process($assistant, $agentInput, $agentOutput);
                });

                $results = $agentOutput->getResultBag()->getResults();
                $result = $results[0] ?? null;
                if ($result !== null) {
                    $content = $result->getContent();
                    $output->writeln('');
                    $output->writeln($content);
                    $output->writeln('');
                    $agentInput->add(Message::ofAssistant($content));
                }
            } catch (\Throwable $e) {
                $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
            }
        }

        $output->writeln('Chat session ended.');
        return Command::SUCCESS;
    }
}
