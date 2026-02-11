<?php

return [
    'ctrl' => [
        'label' => 'name',
        'title' => 'LLL:EXT:ai/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_ai_platform.ctrl.title',
        'typeicon_classes' => [
            'default' => 'mimetypes-x-content-domain',
        ],
    ],
    'columns' => [
        'enabled' => [
            'label' => 'LLL:EXT:ai/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_ai_platform.enabled',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'default' => 0,
            ],
        ],
        'name' => [
            'label' => 'LLL:EXT:ai/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_ai_platform.name',
            'config' => [
                'type' => 'input',
                'required' => true,
                'eval' => 'trim',
            ],
        ],
        'package' => [
            'label' => 'LLL:EXT:ai/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_ai_platform.package',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'required' => true,
                'items' => [
                    ['label' => '', 'value' => ''],
                    ['label' => 'Anthropic', 'value' => 'symfony/ai-anthropic-platform'],
                    ['label' => 'OpenAI', 'value' => 'symfony/ai-openai-platform'],
                    ['label' => 'Google Gemini', 'value' => 'symfony/ai-google-platform'],
                    ['label' => 'Mistral', 'value' => 'symfony/ai-mistral-platform'],
                    ['label' => 'Ollama', 'value' => 'symfony/ai-ollama-platform'],
                    ['label' => 'LM Studio', 'value' => 'symfony/ai-lm-studio-platform'],
                ],
            ],
        ],
        'baseUrl' => [
            'label' => 'LLL:EXT:ai/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_ai_platform.baseUrl',
            'config' => [
                'type' => 'input',
                'eval' => 'trim',
            ],
        ],
        'authorizationType' => [
            'label' => 'LLL:EXT:ai/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_ai_platform.authorizationType',
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
            'label' => 'LLL:EXT:ai/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site_ai_platform.authorizationToken',
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
