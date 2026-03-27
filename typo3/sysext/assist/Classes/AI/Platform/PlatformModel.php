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

namespace TYPO3\CMS\Assist\AI\Platform;

/**
 * @internal
 */
final readonly class PlatformModel implements \Stringable
{
    public function __construct(
        public string $platform,
        public string $model,
        public bool $isLocal = false,
        public bool $suppressThinking = false,
    ) {
        if (\str_contains($this->model, '@') || \str_contains($this->platform, '@')) {
            throw new \LogicException('Identifiers for platform or model may not contain "@" characters.', 1771165074);
        }
    }

    public function __toString(): string
    {
        return $this->model . '@' . $this->platform;
    }
}
