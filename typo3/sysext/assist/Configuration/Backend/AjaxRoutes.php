<?php

declare(strict_types=1);

use TYPO3\CMS\Assist\Controller\Ajax\AssistantAjaxController;
use TYPO3\CMS\Assist\Controller\Ajax\BrowserToolAjaxController;
use TYPO3\CMS\Assist\Controller\Ajax\PlatformAjaxController;

return [
    'assist_get_inline_assistants' => [
        'path' => '/assist/assistant/inline-list',
        'target' => AssistantAjaxController::class . '::getInlineAssistants',
        'methods' => ['POST'],
    ],
    'assist_gate_client_request' => [
        'path' => '/assist/assistant/gate',
        'target' => AssistantAjaxController::class . '::gateClientRequest',
        'methods' => ['POST'],
    ],
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
    'assist_assistant_get_matching_models' => [
        'path' => '/assist/assistant/matching-models',
        'target' => PlatformAjaxController::class . '::getMatchingModels',
        'methods' => ['GET'],
        'inheritAccessFromModule' => 'assist_assistant',
    ],
    'assist_assistant_update_model' => [
        'path' => '/assist/assistant/model/update',
        'target' => PlatformAjaxController::class . '::updateAssistantModel',
        'methods' => ['POST'],
        'inheritAccessFromModule' => 'assist_assistant',
    ],
    'assist_browser_execute_tool' => [
        'path' => '/assist/browser/execute-tool',
        'target' => BrowserToolAjaxController::class . '::executeTool',
        'methods' => ['POST'],
    ],
];
