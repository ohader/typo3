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

defined('TYPO3') or die();

$GLOBALS['TCA']['tt_content']['columns']['header']['config']['fieldWizard']['assistAction'] = [
    'renderType' => 'assistAction',
];
$GLOBALS['TCA']['tt_content']['columns']['subheader']['config']['fieldWizard']['assistAction'] = [
    'renderType' => 'assistAction',
    'options' => [
        'assistants' => ['typo3-assist-inline-chat'],
    ],
];
$GLOBALS['TCA']['tt_content']['columns']['categories']['config']['fieldWizard']['assistAction'] = [
    'renderType' => 'assistAction',
];
