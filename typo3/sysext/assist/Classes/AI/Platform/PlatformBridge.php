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

use Symfony\AI\Platform\ModelCatalog\ModelCatalogInterface;
use Symfony\AI\Platform\PlatformInterface;
use TYPO3\CMS\Assist\Domain\Enum\Availability;
use TYPO3\CMS\Assist\Exception\PlatformNotAvailableException;

/**
 * @internal create instances via PackageService
 */
final readonly class PlatformBridge
{
    public function __construct(
        private \TYPO3\CMS\Assist\Domain\Model\Platform $platform,
        private \TYPO3\CMS\Assist\AI\Platform\PlatformReflector $reflector,
        private bool $effective,
        private array $options = [],
    ) {
        if ($this->effective && $this->platform->availability !== Availability::enabled) {
            throw new PlatformNotAvailableException(
                'Platform ' . $this->platform->name . ' is not available.',
                1771009690
            );
        }
    }

    public function getPlatformFactory(): PlatformInterface
    {
        $class = $this->reflector->getPlatformFactoryClassName();
        return call_user_func(
            $class . '::create',
            ...$this->reflector->getPlatformFactoryOptions($this->options['platformFactory'] ?? [])
        );
    }

    public function getModelCatalog(): ModelCatalogInterface
    {
        $class = $this->reflector->getModelCatalogClassName();
        $modelCatalog = new $class(
            ...$this->reflector->getModelCatalogOptions($this->options['modelCatalog'] ?? [])
        );
        return $this->effective && $this->platform->models !== []
            ? new FilteredModelCatalog($this->platform, $modelCatalog)
            : $modelCatalog;
    }
}
