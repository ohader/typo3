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

use TYPO3\CMS\Assist\Domain\Enum\ResourceType;

/**
 * @internal
 */
final readonly class ResourceSubject implements SubjectInterface
{
    public function __construct(
        public ResourceType $type,
        public int $storage,
        public string $path,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'type' => $this->type->value,
            'storage' => $this->storage,
            'path' => $this->path,
        ];
    }

    public function __toString(): string
    {
        return sprintf('%s@%d:%s', $this->type->value, $this->storage, $this->path);
    }
}
