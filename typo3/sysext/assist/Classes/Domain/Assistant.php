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

namespace TYPO3\CMS\Assist\Domain;

/**
 * @internal
 */
final readonly class Assistant
{
    /**
     * @param string[] $capabilities
     */
    public function __construct(
        public string $identifier,
        public AssistantMode $mode,
        public array $capabilities,
        public string $handler,
        public AssistantTrigger $trigger,
        public string $packageName,
        public string $absolutePackagePath,
    ) {}

    public static function createFromConfiguration(string $identifier, array $configuration): self
    {
        $mode = AssistantMode::tryFrom($configuration['mode'] ?? '');
        if ($mode === null) {
            throw new \InvalidArgumentException(
                sprintf('Invalid mode "%s" for assistant "%s".', $configuration['mode'] ?? '', $identifier),
                1749900001
            );
        }

        $handler = $configuration['handler'] ?? '';
        if (!class_exists($handler)) {
            throw new \InvalidArgumentException(
                sprintf('Handler class "%s" for assistant "%s" does not exist.', $handler, $identifier),
                1749900002
            );
        }

        $capabilities = array_values(array_filter(array_map(
            strval(...),
            $configuration['capabilities'] ?? [],
        )));

        return new self(
            identifier: $identifier,
            mode: $mode,
            capabilities: $capabilities,
            handler: $handler,
            trigger: AssistantTrigger::createFromConfiguration($configuration['trigger'] ?? []),
            packageName: $configuration['packageName'] ?? '',
            absolutePackagePath: $configuration['absolutePackagePath'] ?? '',
        );
    }

    public function hasCapability(string $capability): bool
    {
        return in_array($capability, $this->capabilities, true);
    }
}
