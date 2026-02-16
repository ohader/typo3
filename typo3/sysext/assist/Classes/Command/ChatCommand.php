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

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Uid\Uuid;
use TYPO3\CMS\Assist\AI\Agent\AgentBag;
use TYPO3\CMS\Assist\AI\Agent\AgentConnector;
use TYPO3\CMS\Assist\AI\Agent\SequencePointer;
use TYPO3\CMS\Assist\AI\Platform\PlatformModel;
use TYPO3\CMS\Assist\Domain\Enum\Availability;
use TYPO3\CMS\Assist\Domain\Enum\ProgressItemType;
use TYPO3\CMS\Assist\Domain\Model\Initiator;
use TYPO3\CMS\Assist\Domain\Model\Progress;
use TYPO3\CMS\Assist\Domain\Model\ProgressItem;
use TYPO3\CMS\Assist\Service\ConfigurationResolver;

/**
 * Interactive AI chat session via the command line.
 */
#[AsCommand('assist:chat', 'Start an interactive AI chat session')]
final class ChatCommand extends Command
{
    public function __construct(
        private readonly AgentConnector $agentConnector,
        private readonly ConfigurationResolver $configurationResolver,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('model', InputArgument::REQUIRED, 'The model@platform identifier (see assist:list)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $platformModel = PlatformModel::fromString($input->getArgument('model'));
        } catch (\InvalidArgumentException $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
            return Command::FAILURE;
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

        $uuid = Uuid::v4();
        $initiator = new Initiator(type: 'cli', subject: 'assist:chat');
        $items = [];
        $sequencePointer = new SequencePointer();

        /** @var QuestionHelper $questionHelper */
        $questionHelper = $this->getHelper('question');

        $output->writeln(sprintf('AI Chat — <info>%s</info>', $platformModel));
        $output->writeln('Type your message and press Enter. Send an empty message or type <comment>/quit</comment> to exit.');
        $output->writeln('');

        while (true) {
            $userInput = $questionHelper->ask($input, $output, new Question('<info>You:</info> '));
            if ($userInput === null || $userInput === '' || $userInput === '/quit') {
                break;
            }

            $items[] = new ProgressItem(type: ProgressItemType::submitted, payload: $userInput);

            $progress = new Progress(
                uuid: $uuid,
                model: $platformModel,
                initiator: $initiator,
                items: $items,
            );

            $agentBag = new AgentBag(
                model: $platformModel,
                progress: $progress,
                sequencePointer: $sequencePointer,
            );

            try {
                $result = $this->agentConnector->call($agentBag);
                $content = $result->getContent();
                $output->writeln('');
                $output->writeln($content);
                $output->writeln('');
                $items[] = new ProgressItem(type: ProgressItemType::received, payload: $content);
            } catch (\Throwable $e) {
                $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
            }
        }

        $output->writeln('Chat session ended.');
        return Command::SUCCESS;
    }
}
