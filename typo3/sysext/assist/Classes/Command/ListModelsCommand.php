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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Assist\AI\Platform\PlatformConnector;
use TYPO3\CMS\Assist\AI\Platform\PlatformModel;
use TYPO3\CMS\Assist\Domain\Enum\Availability;
use TYPO3\CMS\Assist\Service\ConfigurationResolver;

/**
 * Lists all available AI platform models.
 */
#[AsCommand('assist:list', 'List available AI platform models')]
final class ListModelsCommand extends Command
{
    public function __construct(
        private readonly ConfigurationResolver $configurationResolver,
        private readonly PlatformConnector $platformConnector,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $platforms = $this->configurationResolver->getDefaultPlatforms();

        if ($platforms === []) {
            $output->writeln('<comment>No platforms configured. Add platforms in the site configuration.</comment>');
            return Command::SUCCESS;
        }

        $entries = [];
        foreach ($platforms as $platform) {
            if ($platform->availability !== Availability::enabled) {
                continue;
            }

            try {
                $bridge = $this->platformConnector->buildBridge($platform);
                $models = $bridge->getModelCatalog()->getModels();
            } catch (\Throwable $e) {
                $output->writeln(sprintf('<comment>Could not load models for platform "%s": %s</comment>', $platform->name, $e->getMessage()));
                continue;
            }

            foreach ($models as $name => $meta) {
                $capabilities = array_map(
                    static fn(object $cap): string => self::shortenCapability($cap->name ?? (string)$cap),
                    $meta['capabilities'] ?? [],
                );
                $identifier = (string)new PlatformModel(platform: $platform->package, model: $name);
                $entries[] = [$identifier, $capabilities];
            }
        }

        if ($entries === []) {
            $output->writeln('No models available on any enabled platform.');
            return Command::SUCCESS;
        }

        foreach ($entries as [$identifier, $capabilities]) {
            $output->writeln(sprintf('<info>%s</info>', $identifier));
            $output->writeln(sprintf('  %s', implode(', ', $capabilities)));
            $output->writeln('');
        }

        return Command::SUCCESS;
    }

    private static function shortenCapability(string $name): string
    {
        $name = str_replace(['INPUT_', 'OUTPUT_'], ['in:', 'out:'], $name);
        return strtolower($name);
    }
}
