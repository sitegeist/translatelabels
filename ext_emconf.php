<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Translatable Labels for Editors',
    'description' => 'This extension extends the TYPO3 translation handling by translation records that can be edited by backend users. In this way backend users are able to translate labels without having access to the language files.',
    'category' => 'fe',
    'author' => 'Alexander Bohndorf',
    'author_email' => 'bohndorf@sitegeist.de',
    'state' => 'beta',
    'version' => '2.3.1',
    'constraints' => [
        'depends' => [
            'typo3' => '10.0.0-11.5.99',
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
