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

use TYPO3\CMS\Assist\Domain\Enum\AssistantCapability;
use TYPO3\CMS\Assist\Domain\Enum\AssistantMode;
use TYPO3\CMS\Assist\Domain\Model\Assistant;

final readonly class AssistantRegistry
{
    /** @var array<string, Assistant> */
    private array $assistants;

    /**
     * @param array<string, array> $assistants Raw configuration arrays keyed by identifier
     */
    public function __construct(array $assistants)
    {
        $indexed = [];
        foreach ($assistants as $identifier => $configuration) {
            $assistant = Assistant::createFromConfiguration($identifier, $configuration);
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
    public function getAssistantsByCapability(AssistantCapability $capability): array
    {
        return array_filter(
            $this->assistants,
            static fn(Assistant $assistant): bool => $assistant->hasCapability($capability),
        );
    }

    /**
     * @return array<string, Assistant>
     */
    public function getAssistantsByTriggerResource(string $resource): array
    {
        return array_filter(
            $this->assistants,
            static fn(Assistant $assistant): bool => $assistant->trigger->hasResource($resource),
        );
    }

    /**
     * @return array<string, Assistant>
     */
    public function getAssistantsByTriggerComponent(string $component): array
    {
        return array_filter(
            $this->assistants,
            static fn(Assistant $assistant): bool => $assistant->trigger->hasComponent($component),
        );
    }
}
