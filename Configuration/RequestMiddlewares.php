<?php
return [
    'backend' => [
        'sitegeist/translatelabels/create-label-resolver' => [
            'target' => Sitegeist\Translatelabels\Middleware\CreateLabelResolver::class,
            'after' => [
                'typo3/cms-backend/authentication',
            ],
        ]
    ]
];
