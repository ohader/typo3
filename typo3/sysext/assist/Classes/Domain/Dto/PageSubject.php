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

namespace TYPO3\CMS\Assist\Domain\Dto;

/**
 * @internal
 */
final readonly class PageSubject implements SubjectInterface
{
    public function __construct(
        public int $uid,
        public int $languageId = 0,
        public int $workspaceId = 0,
    ) {}

    public function jsonSerialize(): array
    {
        return ['uid' => $this->uid, 'languageId' => $this->languageId, 'workspaceId' => $this->workspaceId];
    }

    public static function fromString(string $value): static
    {
        $data = json_decode($value, true, flags: JSON_THROW_ON_ERROR);
        return new static(uid: (int)$data['uid'], languageId: (int)($data['languageId'] ?? 0), workspaceId: (int)($data['workspaceId'] ?? 0));
    }

    public function __toString(): string
    {
        return json_encode(['kind' => 'page'] + $this->jsonSerialize(), JSON_THROW_ON_ERROR);
    }
}
