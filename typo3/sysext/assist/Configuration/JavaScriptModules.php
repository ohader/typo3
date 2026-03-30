<?php

return [
    'dependencies' => [
        'backend',
        'core',
    ],
    'imports' => [
        '@typo3/assist/' => 'EXT:assist/Resources/Public/JavaScript/',
        '@mlc-ai/web-llm' => 'EXT:assist/Resources/Public/JavaScript/Contrib/@mlc-ai/web-llm.js',
    ],
];
