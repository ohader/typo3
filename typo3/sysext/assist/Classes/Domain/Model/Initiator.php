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

namespace TYPO3\CMS\Assist\Domain\Model;

/**
 * @internal
 */
final readonly class Initiator
{
    public function __construct(
        public string $type,
        public string $subject,
    ) {}

    /**
     * @return array{type: string, subject: string}
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'subject' => $this->subject,
        ];
    }

    /**
     * @param array{type: string, subject: string} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            type: $data['type'],
            subject: $data['subject'],
        );
    }

    public static function fromJson(string $json): self
    {
        return self::fromArray(json_decode($json, true, flags: JSON_THROW_ON_ERROR));
    }
}
