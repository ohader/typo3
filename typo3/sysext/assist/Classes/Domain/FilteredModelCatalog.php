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

use Symfony\AI\Platform\ModelCatalog\AbstractModelCatalog;
use Symfony\AI\Platform\ModelCatalog\ModelCatalogInterface;

/**
 * @internal
 */
final class FilteredModelCatalog extends AbstractModelCatalog
{
    public function __construct(
        private readonly Platform $platform,
        private readonly ModelCatalogInterface $originalModelCatalog
    )
    {
        $models = $this->originalModelCatalog->getModels();
        if ($this->platform->models !== []) {
            $models = array_filter(
                $models,
                fn(string $name): bool => in_array($name, $this->platform->models, true),
                ARRAY_FILTER_USE_KEY
            );
        }
        $this->models = $models;
    }
}
