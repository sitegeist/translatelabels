<?php
declare(strict_types = 1);

namespace Sitegeist\Translatelabels\Adminpanel\Modules;

/**
 *
 * This file is part of the "translatelabels" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 *
 */

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\AbstractModule;
use TYPO3\CMS\Adminpanel\ModuleApi\ConfigurableInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\InitializableInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\ResourceProviderInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\ShortInfoProviderInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\PageSettingsProviderInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * TranslateLabel Module of the AdminPanel
 */
class TranslateLabelModule extends AbstractModule implements InitializableInterface, ShortInfoProviderInterface, PageSettingsProviderInterface, ResourceProviderInterface, ConfigurableInterface
{

    /**
     * @inheritdoc
     */
    public function getIdentifier(): string
    {
        return 'translatelabels';
    }

    /**
     * @inheritdoc
     */
    public function getIconIdentifier(): string
    {
        return 'actions-document-localize';
    }

    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return $this->getLanguageService()->sL(
            'LLL:EXT:translatelabels/Resources/Private/Language/locallang_adminpanel.xlf:sub.module.label'
        );
    }

    public function getShortInfo(): string
    {
        return '';
    }

    /**
     * @inheritdoc
     */
    public function initializeModule(ServerRequestInterface $request): void
    {
        $this->config = [
            'showTranslationLabels' => (bool)$this->configurationService->getConfigurationOption('translatelabels', 'showTranslationLabels')
        ];

        // force parsing of TYPOSCRIPT template to get plugin.tx_translatelabels.settings.storagePid
        // fixes #1567012007 TYPO3\CMS\Backend\Exception
        // Missing TYPOSCRIPT: plugin.tx_translatelabels.settings.storagePid not defined.
        // on cached pages with closed admin panel which is clicked to open it. (setting 'show translate labels' is off)
        // needed in public/typo3conf/ext/translatelabels/Classes/Utility/TranslationLabelUtility.php:36
        // to read settings from TYPOSCRIPT also if page is cached
        $GLOBALS['TSFE']->forceTemplateParsing = true;

        if ($this->config['showTranslationLabels']) {
            // forcibly unset fluid caching as it does not care about the tsfe based caching settings
            // pages must not be cached because otherwise the LLL(...) tags are cached and displayed for end users
            unset($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['fluid_template']['frontend']);
            $GLOBALS['TSFE']->set_no_cache('Cache is disabled if fluid debugging is enabled', true);
        }
    }

    /**
     * @return string
     */
    public function getPageSettings(): string
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $templateNameAndPath = 'EXT:translatelabels/Resources/Private/Templates/AdminPanel/Modules/Settings/TranslateLabels.html';
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($templateNameAndPath));
        $view->setPartialRootPaths(['EXT:adminpanel/Resources/Private/Partials']);

        $view->assignMultiple(
            [
                'showTranslationLabels' => $this->configurationService->getConfigurationOption('translatelabels', 'showTranslationLabels')
            ]
        );

        return $view->render();
    }

    /**
     * @return array
     */
    public function getJavaScriptFiles(): array
    {
        return [
            'EXT:translatelabels/Resources/Public/Javascript/popper-1.15.0.min.js',
            'EXT:translatelabels/Resources/Public/Javascript/tippy-4.3.5.min.js',
            'EXT:translatelabels/Resources/Public/Javascript/Modules/Translatelabels.js'
        ];
    }

    /**
     * Returns a string array with css files that will be rendered after the module
     *
     * Example: return ['EXT:adminpanel/Resources/Public/JavaScript/Modules/Edit.css'];
     *
     * @return array
     */
    public function getCssFiles(): array
    {
        return [
            'EXT:translatelabels/Resources/Public/Css/style.css'
        ];
    }
}
