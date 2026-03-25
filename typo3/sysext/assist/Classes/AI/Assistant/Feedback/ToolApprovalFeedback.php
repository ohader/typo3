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

namespace TYPO3\CMS\Assist\AI\Assistant\Feedback;

final readonly class ToolApprovalFeedback implements FeedbackInterface
{
    public function __construct(
        public string $key,
        public string $toolName,
        public array $parameters,
        public ConfirmationItem $approve,
        public ConfirmationItem $decline,
        public ConfirmationItem $alwaysApprove,
    ) {}

    public function getText(): string
    {
        return sprintf('Allow tool call: %s', $this->toolName);
    }

    public function jsonSerialize(): array
    {
        return [
            'type' => 'tool-approval',
            'key' => $this->key,
            'toolName' => $this->toolName,
            'parameters' => $this->parameters,
            'approve' => $this->approve,
            'decline' => $this->decline,
            'alwaysApprove' => $this->alwaysApprove,
        ];
    }
}
