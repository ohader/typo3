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

namespace TYPO3\CMS\Assist\Attribute;

use TYPO3\CMS\Assist\Domain\Enum\AssistantCapability;
use TYPO3\CMS\Assist\Domain\Enum\AssistantMode;

#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class AsAssistant
{
    public const TAG_NAME = 'assist.assistant';

    /**
     * @param list<AssistantCapability> $capabilities
     * @param list<string> $triggerTypes
     * @param list<string> $triggerRecords
     * @param list<string> $triggerComponents
     */
    public function __construct(
        public string $identifier,
        public AssistantMode $mode,
        public array $capabilities,
        public array $triggerTypes = [],
        public array $triggerRecords = [],
        public array $triggerComponents = [],
        public string $labelFile = '',
        public string $javaScriptModule = '',
    ) {}
}
