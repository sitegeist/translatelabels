<?php
declare(strict_types = 1);

namespace Sitegeist\Translatelabels\Adminpanel\Modules\TranslateLabel;

/**
 *
 * This file is part of the "translatelabels" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 *
 */

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\AbstractSubModule;
use TYPO3\CMS\Adminpanel\ModuleApi\ConfigurableInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\ContentProviderInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\DataProviderInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\ModuleData;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Adminpanel\Service\ConfigurationService;

/**
 * RequestInformation submodule of the admin panel
 *
 * @internal
 */
class TranslateLabelInfo extends AbstractSubModule implements DataProviderInterface, ContentProviderInterface, ConfigurableInterface
{
    /**
     * @var ConfigurationService
     */
    protected $configurationService;

    /**
     * @var UriBuilder
     */
    protected $uriBuilder;

    /**
     * @var Context
     */
    protected $context;

    /**
     * TranslateLabel constructor.
     */
    public function __construct()
    {
        $this->configurationService = GeneralUtility::makeInstance(ConfigurationService::class);
        $this->uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $this->context = GeneralUtility::makeInstance(Context::class);
    }

    /**
     * Identifier for this Sub-module,
     * for example "preview" or "cache"
     *
     * @return string
     */
    public function getIdentifier(): string
    {
        return 'translatelabel_translatelabel_info';
    }

    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return $this->getLanguageService()->sL(
            'Language Files'
        );
    }

    /**
     * @inheritdoc
     */
    public function getDataToStore(ServerRequestInterface $request): ModuleData
    {
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        return new ModuleData(
            [
                'labels' => $GLOBALS['TRANSLATELABELS'] ?? [],
                'showTranslateLabels' => $this->configurationService->getConfigurationOption('translatelabels', 'showTranslationLabels'),
                'editIcon' => $iconFactory->getIcon('actions-open', Icon::SIZE_SMALL)->render()
            ]
        );
    }

    /**
     * @inheritdoc
     */
    public function getContent(ModuleData $data): string
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $templateNameAndPath = 'EXT:translatelabels/Resources/Private/Templates/AdminPanel/Modules/TranslateLabels/TranslateLabelsInfo.html';
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($templateNameAndPath));
        $view->setPartialRootPaths(['EXT:adminpanel/Resources/Private/Partials']);

        $view->assignMultiple($data->getArrayCopy());

        return $view->render();
    }

    /**
     * Module is enabled
     * -> should be initialized
     * A module may be enabled but not shown
     * -> only the initializeModule() method
     * will be called
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return true;
        // return (int)$this->configurationService->getConfigurationOption('translatelabels', 'showTranslationLabels') === 1;
    }
}
