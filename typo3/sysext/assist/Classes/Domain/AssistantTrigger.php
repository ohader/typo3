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
final readonly class AssistantTrigger
{
    /**
     * @param array<int, string> $types
     * @param array<int, string> $records
     * @param array<int, string> $components
     */
    public function __construct(
        public array $types = [],
        public array $records = [],
        public array $components = [],
    ) {}

    /**
     * @param array{types?: list<mixed>, records?: list<mixed>, components?: list<mixed>} $configuration
     */
    public static function createFromConfiguration(array $configuration): self
    {
        return new self(
            types: array_values(array_filter(array_map(strval(...), $configuration['types'] ?? []))),
            records: array_values(array_filter(array_map(strval(...), $configuration['records'] ?? []))),
            components: array_values(array_filter(array_map(strval(...), $configuration['components'] ?? []))),
        );
    }

    public function hasType(string $type): bool
    {
        return in_array($type, $this->types, true);
    }

    public function hasRecord(string $record): bool
    {
        return in_array($record, $this->records, true);
    }

    public function hasComponent(string $component): bool
    {
        return in_array($component, $this->components, true);
    }
}
