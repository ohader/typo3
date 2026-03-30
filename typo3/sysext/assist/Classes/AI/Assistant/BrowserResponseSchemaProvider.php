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

/**
 * Optional interface for {@see AssistantInterface} implementations that want to
 * constrain browser-side inference output to a specific JSON Schema.
 *
 * When implemented, {@see AssistantOrchestrator} passes the returned schema
 * to the browser engine, which uses WebLLM's xgrammar backend to guarantee
 * that the model output is valid JSON conforming to the schema.
 *
 * Return null to opt out (unconstrained text output).
 */
interface BrowserResponseSchemaProvider
{
    /**
     * @return array<string, mixed>|null JSON Schema object, or null for unconstrained output
     */
    public function getBrowserResponseSchema(): ?array;
}
