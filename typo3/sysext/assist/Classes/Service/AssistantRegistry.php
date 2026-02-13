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

namespace TYPO3\CMS\Assist\Service;

use TYPO3\CMS\Assist\Domain\Assistant;
use TYPO3\CMS\Assist\Domain\AssistantMode;

final readonly class AssistantRegistry
{
    /** @var array<string, Assistant> */
    private array $assistants;

    /**
     * @param array<string, Assistant> $assistants
     */
    public function __construct(array $assistants)
    {
        $indexed = [];
        foreach ($assistants as $assistant) {
            if (isset($indexed[$assistant->identifier])) {
                throw new \InvalidArgumentException(
                    sprintf('Duplicate assistant identifier "%s".', $assistant->identifier),
                    1749900003
                );
            }
            $indexed[$assistant->identifier] = $assistant;
        }
        $this->assistants = $indexed;
    }

    public function hasAssistant(string $identifier): bool
    {
        return isset($this->assistants[$identifier]);
    }

    public function getAssistant(string $identifier): Assistant
    {
        if (!$this->hasAssistant($identifier)) {
            throw new \InvalidArgumentException(
                sprintf('Assistant "%s" is not registered.', $identifier),
                1749900004
            );
        }
        return $this->assistants[$identifier];
    }

    /**
     * @return array<string, Assistant>
     */
    public function getAssistants(): array
    {
        return $this->assistants;
    }

    /**
     * @return array<string, Assistant>
     */
    public function getAssistantsByMode(AssistantMode $mode): array
    {
        return array_filter(
            $this->assistants,
            static fn(Assistant $assistant): bool => $assistant->mode === $mode,
        );
    }

    /**
     * @return array<string, Assistant>
     */
    public function getAssistantsByCapability(string $capability): array
    {
        return array_filter(
            $this->assistants,
            static fn(Assistant $assistant): bool => $assistant->hasCapability($capability),
        );
    }

    /**
     * @return array<string, Assistant>
     */
    public function getAssistantsByTriggerType(string $type): array
    {
        return array_filter(
            $this->assistants,
            static fn(Assistant $assistant): bool => $assistant->trigger->hasType($type),
        );
    }

    /**
     * @return array<string, Assistant>
     */
    public function getAssistantsByRecord(string $record): array
    {
        return array_filter(
            $this->assistants,
            static fn(Assistant $assistant): bool => $assistant->trigger->hasRecord($record),
        );
    }
}
