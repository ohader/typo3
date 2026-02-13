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
final readonly class Authorization
{
    public function __construct(
        public string $type,
        public string $token,
    ) {
    }

    /**
     * @return array{}|array<string, string>
     */
    public function getHeaderItem(): array
    {
        return match ($this->type) {
            'api-key' => ['X-API-Key' => $this->token],
            'bearer' => ['Authorization' => 'Bearer ' . $this->token],
            default => [],
        };
    }
}
