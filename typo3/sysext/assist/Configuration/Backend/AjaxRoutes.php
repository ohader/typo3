<?php

declare(strict_types=1);

use TYPO3\CMS\Assist\Controller\Ajax\PlatformAjaxController;

return [
    'assist_platform_check_connection' => [
        'path' => '/assist/platform/check-connection',
        'target' => PlatformAjaxController::class . '::checkConnection',
        'methods' => ['POST'],
        'inheritAccessFromModule' => 'assist_platform',
    ],
    'assist_platform_get_models' => [
        'path' => '/assist/platform/models',
        'target' => PlatformAjaxController::class . '::getModels',
        'methods' => ['GET'],
        'inheritAccessFromModule' => 'assist_platform',
    ],
    'assist_platform_update_models' => [
        'path' => '/assist/platform/models/update',
        'target' => PlatformAjaxController::class . '::updateModels',
        'methods' => ['POST'],
        'inheritAccessFromModule' => 'assist_platform',
    ],
];
