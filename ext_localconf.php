<?php

defined('TYPO3') or die();

call_user_func(function () {

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
        "@import 'EXT:translatelabels/Configuration/TSconfig/page.tsconfig'"
    );

    // Register hook after all fe generation, i.e. after inclusion of uncached user_int objects
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-all'][] =
        \Sitegeist\Translatelabels\Hooks\TypoScriptFrontendController::class . '->contentPostProcAll';

    // override f: namespace for fluid to override f:translate
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces']['f'][] = 'Sitegeist\\Translatelabels\\ViewHelpers';

    // override formvh: namespace for fluid to override f:translate
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces']['formvh'][] = 'Sitegeist\\Translatelabels\\ViewHelpers';

    // override fc: namespace for fluid_components to override fc:form.translatedValidationResults
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces']['fc'][] = 'Sitegeist\\Translatelabels\\ViewHelpers\\FluidComponents';

    // xclass TYPO3\CMS\Frontend\Plugin\AbstractPlugin to extend method pi_getLL
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Frontend\\Plugin\\AbstractPlugin'] = array(
        'className' => 'Sitegeist\\Translatelabels\\Plugin\\FrontendLoginController'
    );

    // xclass TYPO3\CMS\Frontend\Plugin\AbstractPlugin to extend method pi_getLL
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Felogin\\Controller\\FrontendLoginController'] = array(
        'className' => 'Sitegeist\\Translatelabels\\Plugin\\FrontendLoginController'
    );

    // Admin Panel Integration
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['adminpanel']['modules']['translatelabels'] = [
        'module' => \Sitegeist\Translatelabels\Adminpanel\Modules\TranslateLabelModule::class,
        'after' => ['debug'],
        'submodules' => [
            'translatelabel' => [
                'module' => \Sitegeist\Translatelabels\Adminpanel\Modules\TranslateLabel\TranslateLabel::class,
            ],
            'info' => [
                'module' => \Sitegeist\Translatelabels\Adminpanel\Modules\TranslateLabel\TranslateLabelInfo::class,
                'after' => ['translatelabel']
            ]
        ]
    ];

    $GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['translatelabels_translate']
        = \Sitegeist\Translatelabels\Controller\AjaxController::class . '::saveDataAction';
});
