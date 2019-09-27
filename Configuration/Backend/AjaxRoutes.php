<?php

/**
 * Definitions for routes provided by EXT:translatelabels
 * Contains all AJAX-based routes for entry points
 */
return [
    'translatelabels_translate' => [
        'path' => '/translatelabels/translate',
        'target' => \Sitegeist\Translatelabels\Controller\AjaxController::class . '::translateAction'
    ]
];
