<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Translatable Labels for Editors',
    'description' => '',
    'category' => 'fe',
    'author' => 'Alexander Bohndorf',
    'author_email' => 'bohndorf@sitegeist.de',
    'state' => 'beta',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 0,
    'version' => '1.1.3',
    'constraints' => [
        'depends' => [
            'typo3' => '9.5.0-9.5.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
    'autoload' => [
        'psr-4' => [
            'Sitegeist\\Translatelabels\\' => 'Classes'
        ]
    ]
];
