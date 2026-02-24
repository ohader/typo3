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

namespace TYPO3\CMS\Assist\AI\Assistant\Question;

final readonly class ConfirmationQuestion implements QuestionInterface
{
    public function __construct(
        public string $text,
        public string $acceptLabel = 'Yes',
        public string $declineLabel = 'No',
    ) {}

    public function getText(): string
    {
        return $this->text;
    }

    public function jsonSerialize(): array
    {
        return [
            'type' => 'confirmation',
            'text' => $this->text,
            'acceptLabel' => $this->acceptLabel,
            'declineLabel' => $this->declineLabel,
        ];
    }
}
