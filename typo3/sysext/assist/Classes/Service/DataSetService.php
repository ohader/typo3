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

namespace TYPO3\CMS\Assist\Service;

use TYPO3\CMS\Assist\Domain\Dto\SubjectInterface;
use TYPO3\CMS\Assist\Domain\Model\Assistant;

/**
 * Returns `data-*` attributes for various aspects.
 *
 * @internal
 */
final readonly class DataSetService
{
    public function forAssistant(Assistant $assistant, ?SubjectInterface $subject = null): array
    {
        $attrs = [
            'data-assistant-identifier' => $assistant->identifier,
            'data-assistant-label-domain' => $assistant->labelDomain,
            'data-assistant-module' => $assistant->additionalModule,
            'data-assistant-input' => $assistant->chatInput->value,
        ];
        if ($subject !== null) {
            $attrs['data-assistant-subject'] = (string)$subject;
        }
        return $attrs;
    }
}
