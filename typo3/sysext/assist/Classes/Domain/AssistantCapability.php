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

use Symfony\AI\Platform\Capability;

enum AssistantCapability: string
{
    case text = 'text';
    case image = 'image';
    case toolCall = 'tool_call';

    /**
     * @return list<Capability>
     */
    public function getRequiredCapabilities(): array
    {
        return match ($this) {
            self::text => [Capability::INPUT_MESSAGES, Capability::OUTPUT_TEXT],
            self::image => [Capability::INPUT_IMAGE],
            self::toolCall => [Capability::TOOL_CALLING],
        };
    }
}
