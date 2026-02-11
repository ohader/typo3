<?php

$GLOBALS['SiteConfiguration']['site']['columns']['aiDefault'] = [
    'label' => 'LLL:EXT:ai/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site.aiDefault',
    'config' => [
        'type' => 'check',
        'renderType' => 'checkboxToggle',
        'eval' => 'unique',
        'default' => 0,
    ],
];
$GLOBALS['SiteConfiguration']['site']['columns']['aiPlatforms'] = [
    'label' => 'LLL:EXT:ai/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site.aiPlatforms',
    'config' => [
        'type' => 'inline',
        'foreign_table' => 'site_ai_platform',
        'appearance' => [
            'collapseAll' => true,
            'enabledControls' => [
                'info' => false,
            ],
        ],
    ],
];

$GLOBALS['SiteConfiguration']['site']['types']['0']['showitem'] .= ',
    --div--;LLL:EXT:ai/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site.tab.ai, --palette--;;ai, aiPlatforms';

$GLOBALS['SiteConfiguration']['site']['palettes']['ai'] = [
    'showitem' => 'aiDefault',
];
