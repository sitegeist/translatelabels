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
use TYPO3\CMS\Adminpanel\ModuleApi\RequestEnricherInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\ResourceProviderInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\ShortInfoProviderInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\PageSettingsProviderInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\TypoScriptAspect;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * TranslateLabel Module of the AdminPanel
 */
class TranslateLabelModule extends AbstractModule implements ShortInfoProviderInterface, PageSettingsProviderInterface, ResourceProviderInterface, ConfigurableInterface, RequestEnricherInterface
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
    public function enrich(ServerRequestInterface $request): ServerRequestInterface
    {
        // force parsing of TYPOSCRIPT template to get plugin.tx_translatelabels.settings.storagePid
        // fixes #1567012007 TYPO3\CMS\Backend\Exception
        // Missing TYPOSCRIPT: plugin.tx_translatelabels.settings.storagePid not defined.
        // on cached pages with closed admin panel which is clicked to open it. (setting 'show translate labels' is off)
        // needed in typo3conf/ext/translatelabels/Classes/Utility/TranslationLabelUtility.php:39
        // to read settings from TYPOSCRIPT also if page is cached
        GeneralUtility::makeInstance(Context::class)->setAspect('typoscript', GeneralUtility::makeInstance(TypoScriptAspect::class, true));

        if ((bool)$this->configurationService->getConfigurationOption('translatelabels', 'showTranslationLabels')) {
            $request = $request->withAttribute('noCache', true);
        }
        return $request;
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
