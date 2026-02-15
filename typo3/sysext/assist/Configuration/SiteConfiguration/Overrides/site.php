<?php

$GLOBALS['SiteConfiguration']['site']['columns']['assistDefault'] = [
    'label' => 'LLL:EXT:assist/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site.assistDefault',
    'config' => [
        'type' => 'check',
        'renderType' => 'checkboxToggle',
        'eval' => 'unique',
        'default' => 0,
    ],
];
$GLOBALS['SiteConfiguration']['site']['columns']['assistPlatforms'] = [
    'label' => 'LLL:EXT:assist/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site.assistPlatforms',
    'config' => [
        'type' => 'inline',
        'foreign_table' => 'site_assist_platform',
        'appearance' => [
            'collapseAll' => true,
            'useSortable' => true,
            'enabledControls' => [
                'info' => false,
            ],
        ],
    ],
];

$GLOBALS['SiteConfiguration']['site']['types']['0']['showitem'] .= ',
    --div--;LLL:EXT:assist/Resources/Private/Language/locallang_siteconfiguration_tca.xlf:site.tab.assist, --palette--;;assist, assistPlatforms';

$GLOBALS['SiteConfiguration']['site']['palettes']['assist'] = [
    'showitem' => 'assistDefault',
];
