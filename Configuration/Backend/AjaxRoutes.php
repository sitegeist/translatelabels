<?php

use Sitegeist\Translatelabels\Controller\AjaxController;

/**
 * Definitions for routes provided by EXT:translatelabels
 * Contains all AJAX-based routes for entry points
 */
return [
    'translatelabels_translate' => [
        'path' => '/translatelabels/translate',
        'target' => AjaxController::class . '::translateAction'
    ]
];
