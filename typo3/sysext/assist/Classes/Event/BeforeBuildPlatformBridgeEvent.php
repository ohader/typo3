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

namespace TYPO3\CMS\Assist\Event;

use TYPO3\CMS\Assist\AI\Platform\PlatformReflector;
use TYPO3\CMS\Assist\Domain\Model\Platform;

/**
 * @internal
 */
final class BeforeBuildPlatformBridgeEvent
{
    /**
     * @var array{platformFactory?: array<string, mixed>}
     */
    private array $options = [];

    private bool $liveResolved = false;

    public function __construct(
        public readonly Platform $platform,
        public readonly PlatformReflector $reflector,
        public bool $effective,
        array $options,
    ) {
        $this->options = $options;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    public function markLiveResolved(): void
    {
        $this->liveResolved = true;
    }

    public function isLiveResolved(): bool
    {
        return $this->liveResolved;
    }
}
