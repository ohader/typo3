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

namespace TYPO3\CMS\Assist\AI\Agent;

use Symfony\AI\Platform\Result\ResultInterface;

/**
 * Persists progress items (submitted user messages and received assistant
 * responses) to the database.
 *
 * @internal
 */
interface ProgressRecorderInterface
{
    public function recordSubmitted(AgentCallRequest $request): void;
    public function recordReceived(AgentCallRequest $request, ResultInterface $result): void;
}
