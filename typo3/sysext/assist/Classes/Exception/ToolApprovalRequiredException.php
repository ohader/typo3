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

namespace TYPO3\CMS\Assist\Exception;

/**
 * Thrown by {@see \TYPO3\CMS\Assist\AI\Agent\ApprovingToolbox} when a tool call
 * has not yet been approved by the user. Caught by assistant handlers to return
 * a {@see \TYPO3\CMS\Assist\AI\Assistant\Feedback\ToolApprovalFeedback}.
 */
final class ToolApprovalRequiredException extends Exception
{
    public function __construct(
        public readonly string $toolName,
        public readonly array $parameters,
    ) {
        parent::__construct(
            sprintf('Tool "%s" requires user approval before execution.', $this->toolName),
            1743255000,
        );
    }
}
