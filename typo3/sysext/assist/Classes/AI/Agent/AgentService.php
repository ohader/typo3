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
 * Orchestrates {@see AgentGateway} (transport) and
 * {@see ProgressRecorderInterface} (persistence).
 *
 * @internal
 */
final readonly class AgentService
{
    public function __construct(
        private AgentGateway $agentGateway,
        private ProgressRecorderInterface $progressRecorder,
    ) {}

    public function call(AgentCallRequest $request): ResultInterface
    {
        $this->progressRecorder->recordSubmitted($request);
        $result = $this->agentGateway->call($request);
        $this->progressRecorder->recordReceived($request, $result);
        return $result;
    }
}
