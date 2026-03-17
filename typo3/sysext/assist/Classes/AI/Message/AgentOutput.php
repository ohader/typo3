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

namespace TYPO3\CMS\Assist\AI\Message;

use Symfony\AI\Platform\Result\ResultInterface;
use Symfony\AI\Platform\TokenUsage\TokenUsageInterface;

final class AgentOutput implements AgentOutputInterface
{
    private AgentResultBag $resultBag;

    private TokenConsumption $tokenConsumption;

    public function __construct(ResultInterface ...$results)
    {
        $this->resultBag = new AgentResultBag(...$results);
        $this->tokenConsumption = new TokenConsumption();
    }

    public function add(ResultInterface $result): void
    {
        $this->resultBag->add($result);
        $usage = $result->getMetadata()->get('token_usage');
        if ($usage instanceof TokenUsageInterface) {
            $tokenConsumption = TokenConsumption::fromTokenUsageInterface($usage);
            $this->tokenConsumption = $this->tokenConsumption->add($tokenConsumption);
        }
    }

    public function getResultBag(): AgentResultBag
    {
        return $this->resultBag;
    }

    public function getTokenConsumption(): TokenConsumption
    {
        return $this->tokenConsumption;
    }
}
