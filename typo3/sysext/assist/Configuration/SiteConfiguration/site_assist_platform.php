<?php

use TYPO3\CMS\Assist\Backend\PlatformItemsProcFunc;

return [
    'ctrl' => [
        'label' => 'name',
        'title' => 'LLL:EXT:assist/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_assist_platform.ctrl.title',
        'typeicon_classes' => [
            'default' => 'mimetypes-x-content-domain',
        ],
    ],
    'columns' => [
        'enabled' => [
            'label' => 'LLL:EXT:assist/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_assist_platform.enabled',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'default' => 0,
            ],
        ],
        'name' => [
            'label' => 'LLL:EXT:assist/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_assist_platform.name',
            'config' => [
                'type' => 'input',
                'required' => true,
                'eval' => 'trim',
            ],
        ],
        'package' => [
            'label' => 'LLL:EXT:assist/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_assist_platform.package',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'required' => true,
                'itemsProcFunc' => PlatformItemsProcFunc::class . '->getInstalledPlatformPackages',
                'items' => [
                    ['label' => '', 'value' => ''],
                ],
            ],
        ],
        'baseUrl' => [
            'label' => 'LLL:EXT:assist/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_assist_platform.baseUrl',
            'config' => [
                'type' => 'input',
                'eval' => 'trim',
            ],
        ],
        'authorizationType' => [
            'label' => 'LLL:EXT:assist/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_assist_platform.authorizationType',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => 'None', 'value' => 'none'],
                    ['label' => 'Bearer Token', 'value' => 'bearer'],
                    ['label' => 'API Key', 'value' => 'api-key'],
                ],
                'default' => 'none',
            ],
        ],
        'authorizationToken' => [
            'label' => 'LLL:EXT:assist/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_assist_platform.authorizationToken',
            'config' => [
                'type' => 'input',
                'eval' => 'trim',
            ],
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => 'enabled, name, package, baseUrl, --linebreak--, authorizationType, authorizationToken',
        ],
    ],
];
