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

use TYPO3\CMS\Assist\Domain\Platform;

/**
 * @internal
 */
final class BeforeBuildPlatformBridgeEvent
{
    /**
     * @var array{platformFactory?: array<string, mixed>}
     */
    private array $options = [];

    public function __construct(
        public readonly Platform $platform,
        public readonly string $namespace,
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
}
