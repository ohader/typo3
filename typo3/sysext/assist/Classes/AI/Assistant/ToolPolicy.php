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

use TYPO3\CMS\Assist\AI\Message\AgentInput;
use TYPO3\CMS\Assist\Domain\Model\Assistant;

interface ToolPolicy
{
    /**
     * @return list<class-string> Tool class names to activate for this call.
     */
    public function resolveTools(Assistant $assistant, AgentInput $input): array;
}
