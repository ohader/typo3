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

use TYPO3\CMS\Assist\Attribute\AsAssistant;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

trait AssistantContextTrait
{
    private function resolveAssistantAttribute(AssistantInterface $target): AsAssistant
    {
        $reflector = new \ReflectionClass($target);
        $attributes = $reflector->getAttributes(AsAssistant::class);
        if ($attributes === []) {
            throw new \LogicException(
                sprintf(
                'Assistant "%s" must have an attribute of type "%s".',
                $target::class,
                AsAssistant::class
                ),
                1773217744
            );
        }
        return $attributes[0]->newInstance();
    }

    private function getBackendUserLanguageHint(): string
    {
        $lang = $this->getBackendUser()->user['lang'] ?? 'en';
        if ($lang === '' || $lang === 'default') {
            $lang = 'en';
        }
        return sprintf(
            'The backend user\'s interface language is "%s". Respond in that language unless the user instructs otherwise.',
            $lang,
        );
    }

    private function getBackendUserId(): int
    {
            return $this->getBackendUser()->user['uid'] ?? 0;
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
