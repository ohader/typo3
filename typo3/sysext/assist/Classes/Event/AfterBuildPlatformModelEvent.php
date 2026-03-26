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

use TYPO3\CMS\Assist\AI\Platform\PlatformModel;

/**
 * Fired after a {@see PlatformModel} has been built from an identifier string.
 * Listeners may replace the model to adjust its properties (e.g. set {@see PlatformModel::$isLocal}).
 */
final class AfterBuildPlatformModelEvent
{
    public function __construct(private PlatformModel $platformModel) {}

    public function getPlatformModel(): PlatformModel
    {
        return $this->platformModel;
    }

    public function setPlatformModel(PlatformModel $platformModel): void
    {
        $this->platformModel = $platformModel;
    }
}
