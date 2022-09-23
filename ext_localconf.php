<?php
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use Rathch\Translatelabels\Hooks\TypoScriptFrontendController;
use Rathch\Translatelabels\Adminpanel\Modules\TranslateLabelModule;
use Rathch\Translatelabels\Adminpanel\Modules\TranslateLabel\TranslateLabel;
use Rathch\Translatelabels\Adminpanel\Modules\TranslateLabel\TranslateLabelInfo;
use Rathch\Translatelabels\Controller\AjaxController;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Page\PageRenderer;
defined('TYPO3') or die();

call_user_func(function () {

    ExtensionManagementUtility::addPageTSConfig(
        "@import 'EXT:translatelabels/Configuration/TSconfig/page.tsconfig'"
    );

    // Register hook after all fe generation, i.e. after inclusion of uncached user_int objects
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['hook_eofe'][] =
        TypoScriptFrontendController::class . '->contentPostProcAll';

    // override f: namespace for fluid to override f:translate
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces']['f'][] = 'Rathch\\Translatelabels\\ViewHelpers';

    // override formvh: namespace for fluid to override f:translate
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces']['formvh'][] = 'Rathch\\Translatelabels\\ViewHelpers';

    // override fc: namespace for fluid_components to override fc:form.translatedValidationResults
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces']['fc'][] = 'Rathch\\Translatelabels\\ViewHelpers\\FluidComponents';

    // xclass TYPO3\CMS\Frontend\Plugin\AbstractPlugin to extend method pi_getLL
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Frontend\\Plugin\\AbstractPlugin'] = array(
        'className' => 'Rathch\\Translatelabels\\Plugin\\FrontendLoginController'
    );

    // xclass TYPO3\CMS\Frontend\Plugin\AbstractPlugin to extend method pi_getLL
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Felogin\\Controller\\FrontendLoginController'] = array(
        'className' => 'Rathch\\Translatelabels\\Plugin\\FrontendLoginController'
    );

    // Admin Panel Integration
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['adminpanel']['modules']['translatelabels'] = [
        'module' => TranslateLabelModule::class,
        'after' => ['debug'],
        'submodules' => [
            'translatelabel' => [
                'module' => TranslateLabel::class,
            ],
            'info' => [
                'module' => TranslateLabelInfo::class,
                'after' => ['translatelabel']
            ]
        ]
    ];

    $GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['translatelabels_translate']
        = AjaxController::class . '::saveDataAction';

    // Avoid spinner after loading BE form:
    $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
    $pageRenderer->addInlineSetting('ContextHelp', 'moduleUrl', '');
    $dateFormat = ($GLOBALS['TYPO3_CONF_VARS']['SYS']['USdateFormat'] ? ['MM-DD-YYYY', 'HH:mm MM-DD-YYYY'] : ['DD-MM-YYYY', 'HH:mm DD-MM-YYYY']);
    $pageRenderer->addInlineSetting('DateTimePicker', 'DateFormat', $dateFormat);
});
