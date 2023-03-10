<?php

namespace Sitegeist\Translatelabels\Utility;

/**
 *
 * This file is part of the "translatelabels" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 *
 */
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Http\ApplicationType;
use Psr\Http\Message\ServerRequestInterface;
use Sitegeist\Translatelabels\Domain\Model\Translation;
use TYPO3\CMS\Backend\Exception;
use TYPO3\CMS\Core\Http\ServerRequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Core\Context\Context;
use Sitegeist\Translatelabels\Domain\Repository\TranslationRepository;
use TYPO3\CMS\Adminpanel\Service\ConfigurationService;

class TranslationLabelUtility
{
    /**
     * returns storagePid where to store translation records
     *
     * @return null
     * @throws Exception
     * @throws \TYPO3\CMS\Extbase\Object\Exception
     */
    public static function getStoragePid()
    {
        // TYPOSCRIPT setup is only defined in TSFE if page is uncached and TYPO_MODE === 'FE'
        // @see typo3conf/ext/translatelabels/Classes/Adminpanel/Modules/TranslateLabelModule.php:133
        // to enforce parsing of TYPOSCRIPT setting $GLOBALS['TSFE']->forceTemplateParsing = true;
        $storagePid = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_translatelabels.']['settings.']['storagePid'] ?? null;
        if ($storagePid === null) {
            throw new Exception('Missing TYPOSCRIPT: plugin.tx_translatelabels.settings.storagePid not defined.', 1567012007);
        }
        return $storagePid;
    }

    /**
     * @param string $labelKey Translation Key compatible to TYPO3 Flow
     * @param string $fallBackTranslation  current translation from LocalizationUtility::translate
     *                                     (will be used if no record is found)
     * @return string                      new translation, maybe overridden from translation record if defined
     * @throws Exception
     * @throws \TYPO3\CMS\Extbase\Object\Exception
     */
    public static function readLabelFromDatabase(string $labelKey, string $fallBackTranslation = null)
    {
        $translation = self::getLabelFromDatabase($labelKey, self::getStoragePid());
        if ($translation !== null) {
            $fallBackTranslation = $translation->getTranslation();
        }
        return $fallBackTranslation;
    }

    /**
     * @param string $labelKey         Translation Key
     * @param int $pid                 pid of sysfolder where to search for translation records (needed for calls from BE)
     * @param int | null $languageUid  uid of language of translation label, null means current language

     * @return object                  translation from database or null if it doesn't exist
     * @throws \TYPO3\CMS\Extbase\Object\Exception
     */
    public static function getLabelFromDatabase(string $labelKey, int $pid, int $languageUid = null)
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        /**
         * @var $translationRepository TranslationRepository
         */
        $translationRepository = $objectManager->get(TranslationRepository::class);
        if ($languageUid === null) {
            $translation = $translationRepository->findOneByLabelKeyInPid($labelKey, $pid);
        } else {
            $translation = $translationRepository->findOneByLabelKeyInLanguageInPid($labelKey, $languageUid, $pid);
        }
        return $translation;
    }

    /**
     * @param $labelKey
     * @param $translation
     * @throws \TYPO3\CMS\Extbase\Object\Exception
     * @throws IllegalObjectTypeException
     */
    public static function createLabel($labelKey, $translation)
    {

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        /**
         * @var $translationRepository TranslationRepository
         */
        $translationRepository = $objectManager->get(TranslationRepository::class);
        $translationObj = new Translation();
        $translationObj->setLabelkey($labelKey);
        $translationObj->setTranslation($translation);
        return $translationRepository->add($translationObj);
    }

    /**
     * renders the translation and the key in a LLL:("<translation>","<key>") tag
     * that can be post processed or directly printed
     *
     * @param $labelKey
     * @param $translation
     * @param null $extensionName
     * @param null $translateArguments
     * @param null $languageKey
     * @param null $alternativeLanguageKeys
     *
     * @return string
     */
    public static function renderTranslationWithExtendedInformation(
        $labelKey,
        $translation,
        $extensionName = null,
        $translateArguments = null,
        $languageKey = null,
        $alternativeLanguageKeys = null
    ) {
        $separator = '\'';
        return 'LLL:(' .
            $separator . $translation . $separator . ',' .
            $separator . $labelKey . $separator . ',' .
            $separator . $extensionName . $separator . ',' .
            $separator . $languageKey . $separator . ',' .
            $separator . $alternativeLanguageKeys . $separator .
            ')';
    }

    /**
     * @param string $labelKey
     * @param string $extensionName
     * @return bool
     * @throws AspectNotFoundException
     */
    public static function isFrontendWithLoggedInBEUser($labelKey = '', $extensionName = '')
    {
        $showTranslationLabels = false;

        /** @var ServerRequestInterface $request */
        $request = $GLOBALS['TYPO3_REQUEST'] ?? ServerRequestFactory::fromGlobals();
        $context = GeneralUtility::makeInstance(Context::class);
        $isLoggedIn = $context->getPropertyFromAspect('backend.user', 'id');
        if ($isLoggedIn) {
            $adminPanelConfigurationService = GeneralUtility::makeInstance(ConfigurationService::class);
            $showTranslationLabels = $adminPanelConfigurationService->getConfigurationOption(
                'translatelabels',
                'showTranslationLabels'
            );
        }
        return ($isLoggedIn !== 0
            && ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isFrontend()
            && strpos($labelKey, 'adminpanel') !== 0
            && $extensionName !== 'adminpanel'
            && $showTranslationLabels === '1'
            // only for uncached pages as no_cache is set by admin panel
            // otherwise we could generate LLL tags into cached pages and will not replace them
            // in FE if no BE user is logged-in.
            && $request->getAttribute('noCache') === true
        );
    }

    /**
     * @param $extensionName
     * @return string
     */
    public static function getDefaultLanguageFile($extensionName)
    {
        return GeneralUtility::camelCaseToLowerCaseUnderscored($extensionName) . '/Resources/Private/Language/locallang.xlf';
    }

    /**
     * @param $labelKey
     * @param $extensionName
     * @return string
     */
    public static function getExtendLabelKeyWithLanguageFilePath($labelKey, $extensionName)
    {
        $reversedParts = explode(':', strrev($labelKey), 2);
        $languageFile = strrev($reversedParts[1] ?? '');
        $extendedLabelKey = $labelKey;
        if ($languageFile === '') {
            // $extendedLabelKey = 'LLL:EXT:' . self::getDefaultLanguageFile($extensionName) . ':' . $labelKey;
            $extendedLabelKey = self::getDefaultLanguageFile($extensionName) . ':' . $labelKey;
        }
        return self::getLabelKeyWithoutPrefixes($extendedLabelKey);
    }

    /**
     * @param $labelKey
     * @return false|mixed|string
     */
    public static function getLabelKeyWithoutPrefixes($labelKey)
    {
        // remove leading 'LLL:'
        $labelKey = (strpos($labelKey, 'LLL:') === 0) ? substr($labelKey, 4) : $labelKey;
        // remove leading 'EXT:'
        $labelKey = (strpos($labelKey, 'EXT:') === 0) ? substr($labelKey, 4) : $labelKey;
        return $labelKey;
    }

    /**
     * replaces all html tags from $content and replaces <br>, <br />, <br/>, <div> with newline
     *
     * @param $content
     * @return string
     */
    public static function stripAllTagsButNewlines($content)
    {
        return (strip_tags(str_replace(["<br/>\n", "<br />\n", "<br>\n",'<div>'], ["\n", "\n", "\n", "\n"], $content)));
    }
}
