<?php

use Sitegeist\Translatelabels\Middleware\ReplaceLabels;
use Sitegeist\Translatelabels\Middleware\CreateLabelResolver;
use Sitegeist\Translatelabels\Middleware\SetJavaScriptSettings;

return [
    'backend' => [
        'sitegeist/translatelabels/create-label-resolver' => [
            'target' => CreateLabelResolver::class,
            'after' => [
                'typo3/cms-backend/authentication',
            ],
        ]
    ],
    'frontend' => [
        'sitegeist/translatelabels/replace-labels' => [
            'target' => ReplaceLabels::class,
            'after' => [
                'typo3/cms-frontend/maintenance-mode'
            ]
        ],
        'sitegeist/translatelabels/set-javascript-settings' => [
            'target' => SetJavaScriptSettings::class,
            'after' => [
                'typo3/cms-adminpanel/initiator'
            ]
        ]
    ]
];
