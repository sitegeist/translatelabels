<?php

namespace Sitegeist\Translatelabels\Plugin;

use TYPO3\CMS\Backend\Exception;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use Sitegeist\Translatelabels\Utility\TranslationLabelUtility;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

/**
 *
 * This file is part of the "translatelabels" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 *
 */

class FrontendLoginController extends \TYPO3\CMS\Felogin\Controller\FrontendLoginController
{

    protected $languageFilePath = '';

    /**
     * Class Constructor (true constructor)
     * Initializes $this->piVars if $this->prefixId is set to any value
     * Will also set $this->LLkey based on the config.language setting.
     *
     * fix setting of $this->LLkey in respect of SiteLanguage in the same manner as
     * TypoScriptFrontendController
     * @see \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::settingLanguage
     *
     * @param null $_ unused,
     * @param TypoScriptFrontendController $frontendController
     */
    public function __construct($_ = null, TypoScriptFrontendController $frontendController = null)
    {
        parent::__construct($_, $frontendController);

        $siteLanguage = $this->getCurrentSiteLanguage();

        // Initialize charset settings etc.
        if ($siteLanguage) {
            $this->LLkey = $siteLanguage->getTypo3Language();
        } else {
            if (!empty($this->frontendController->config['config']['language'])) {
                $this->LLkey = $this->frontendController->config['config']['language'];
                if (empty($this->frontendController->config['config']['language_alt'])) {
                    /** @var Locales $locales */
                    $locales = GeneralUtility::makeInstance(Locales::class);
                    if (\in_array($this->LLkey, $locales->getLocales())) {
                        $this->altLLkey = '';
                        foreach ($locales->getLocaleDependencies($this->LLkey) as $language) {
                            $this->altLLkey .= $language . ',';
                        }
                        $this->altLLkey = rtrim($this->altLLkey, ',');
                    }
                } else {
                    $this->altLLkey = $this->frontendController->config['config']['language_alt'];
                }
            }
        }
    }

    /***************************
     *
     * Localization, locallang functions
     *
     **************************/
    /**
     * Returns the localized label of the LOCAL_LANG key, $key
     * Notice that for debugging purposes prefixes for the output values can be set with the internal
     * vars ->LLtestPrefixAlt and ->LLtestPrefix
     *
     * @param string $key The key from the LOCAL_LANG array for which to return the value.
     * @param string $alternativeLabel Alternative string to return IF no value is found set for the key,
     * neither for the local language nor the default.
     * @return string The value from LOCAL_LANG.
     * @throws Exception
     * @throws AspectNotFoundException
     */
    public function pi_getLL($key, $alternativeLabel = '') // phpcs:disable
    {
        $label = parent::pi_getLL($key, $alternativeLabel);
        $id =  TranslationLabelUtility::getLabelKeyWithoutPrefixes($this->languageFilePath . ':' . $key);

        $label = TranslationLabelUtility::readLabelFromDatabase($id, $label);
        if (TranslationLabelUtility::isFrontendWithLoggedInBEUser($id)) {
            $label = TranslationLabelUtility::renderTranslationWithExtendedInformation($id, $label, $this->prefixId);
        }

        return $label;
    }

    /**
     * Loads local-language values from the file passed as a parameter or
     * by looking for a "locallang" file in the
     * plugin class directory ($this->scriptRelPath).
     * Also locallang values set in the TypoScript property "_LOCAL_LANG" are
     * merged onto the values found in the "locallang" file.
     * Supported file extensions xlf, xml
     *
     * @param string $languageFilePath path to the plugin language file in format EXT:....
     * @return mixed
     */
    public function pi_loadLL($languageFilePath = '') // phpcs:disable
    {
        $this->languageFilePath = $languageFilePath;
        return parent::pi_loadLL($languageFilePath);
    }

    /**
     * Returns the currently configured "site language" if a site is configured (= resolved) in the current request.
     * @see \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::getCurrentSiteLanguage
     *
     * @internal
     */
    protected function getCurrentSiteLanguage(): ?SiteLanguage
    {
        if (isset($GLOBALS['TYPO3_REQUEST'])
            && $GLOBALS['TYPO3_REQUEST'] instanceof ServerRequestInterface
            && $GLOBALS['TYPO3_REQUEST']->getAttribute('language') instanceof SiteLanguage) {
            return $GLOBALS['TYPO3_REQUEST']->getAttribute('language');
        }
        return null;
    }
}
