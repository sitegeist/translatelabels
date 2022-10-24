<?php

use Sitegeist\Translatelabels\Middleware\CreateLabelResolver;
return [
    'backend' => [
        'sitegeist/translatelabels/create-label-resolver' => [
            'target' => CreateLabelResolver::class,
            'after' => [
                'typo3/cms-backend/authentication',
            ],
        ]
    ]
];
