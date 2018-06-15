<?php
return [
    'sys_language' => [
        '__identity' => [
            'passive' => [
                'sys_language.__identity <- content.language',
                'sys_language.__identity <- content.entities',
                'sys_language.__identity <- image.language',
                'sys_language.__identity <- category.items',
            ],
        ],
    ],
    'content' => [
        'language' => [
            'active' => [
                'content.language -> sys_language',
            ],
        ],
        'layout' => [
            'active' => [
                'content.layout -> layout',
            ],
        ],
        'imageReference' => [
            'active' => [
                'content.imageReference -> image',
            ],
        ],
        'imageComposition' => [
            'active' => [
                'content.imageComposition -> image.content',
            ],
        ],
        'categories' => [
            'active' => [
                'content.categories -> category.items',
            ],
        ],
        'entities' => [
            'active' => [
                'content.entities -> sys_language',
                'content.entities -> content',
                'content.entities -> image',
                'content.entities -> layout',
                'content.entities -> category',
            ],
        ],
        'related' => [
            'active' => [
                'content.related -> content',
                'content.related -> image',
            ],
        ],
        '__identity' => [
            'passive' => [
                'content.__identity <- content.entities',
                'content.__identity <- content.related',
                'content.__identity <- category.items',
            ],
        ],
    ],
    'image' => [
        'language' => [
            'active' => [
                'image.language -> sys_language',
            ],
        ],
        '__identity' => [
            'passive' => [
                'image.__identity <- content.imageReference',
                'image.__identity <- content.entities',
                'image.__identity <- content.related',
                'image.__identity <- category.items',
            ],
        ],
        'content' => [
            'passive' => [
                'image.content <- content.imageComposition',
            ],
        ],
    ],
    'layout' => [
        '__identity' => [
            'passive' => [
                'layout.__identity <- content.layout',
                'layout.__identity <- content.entities',
                'layout.__identity <- category.items',
            ],
        ],
    ],
    'category' => [
        'items' => [
            'active' => [
                'category.items -> sys_language',
                'category.items -> content',
                'category.items -> image',
                'category.items -> layout',
                'category.items -> category',
            ],
            'passive' => [
                'category.items <- content.categories',
            ],
        ],
        '__identity' => [
            'passive' => [
                'category.__identity <- content.entities',
                'category.__identity <- category.items',
            ],
        ],
    ],
];
