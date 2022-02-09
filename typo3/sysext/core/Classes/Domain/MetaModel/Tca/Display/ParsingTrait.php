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

namespace TYPO3\CMS\Core\Domain\MetaModel\Tca\Display;

use TYPO3\CMS\Core\Utility\GeneralUtility;

trait ParsingTrait
{
    /**
     * @param string $data
     * @return list<string>
     */
    private static function explodeShowItem(string $data): array
    {
        $data = trim($data, ", \0\t\v\n\r");
        return GeneralUtility::trimExplode(',', $data, true);
    }

    private static function parseTypeShowItems(string $data): array
    {
        return array_map(
            function (string $item) {
                $parts = GeneralUtility::trimExplode(';', $item);
                return match ($parts[0]) {
                    '--div--' => TabRef::fromString($item),
                    '--palette--' => PaletteRef::fromString($item),
                    default => FieldRef::fromString($item),
                };
            },
            self::explodeShowItem($data)
        );
    }

    private static function parsePaletteShowItem(string $data): array
    {
        return array_map(
            function (string $item) {
                $parts = GeneralUtility::trimExplode(';', $item);
                return match ($parts[0]) {
                    '--linebreak--' => new LinebreakRef(),
                    default => FieldRef::fromString($item),
                };
            },
            self::explodeShowItem($data)
        );
    }
}
