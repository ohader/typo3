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

namespace TYPO3\CMS\AI\Service;

use TYPO3\CMS\Core\Site\SiteFinder;

/**
 * Resolves platforms from site configuration (`ai` section in `sites/my-site/config.yaml`).
 */
final readonly class PlatformResolver
{
    public function __construct(private SiteFinder $siteFinder) {}

    public function getPlatforms(string $siteIdentifier): array
    {

    }
}
