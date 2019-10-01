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

use Sitegeist\Translatelabels\Domain\Model\Translation;
use TYPO3\CMS\Backend\Exception;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\BackendConfigurationManager;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Core\Context\Context;
use Sitegeist\Translatelabels\Domain\Repository\TranslationRepository;
use TYPO3\CMS\Adminpanel\Service\ConfigurationService;

class TranslationLabelUtility
{
    /**
     * returns storagePid where to store translation records

     * @return int | null
     * @throws Exception
     */
    public static function getStoragePid()
    {
        // TYPOSCRIPT setup is only defined if page is uncached and TYPO_MODE === 'FE'
        $storagePid = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_translatelabels.']['settings.']['storagePid'] ?? null;
        if ($storagePid === null) {
            // this is an edge case if page is cached but admin panel is active
            $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
            $configurationManager = $objectManager->get(BackendConfigurationManager::class);
            $configuration = $configurationManager->getConfiguration('translatelabels');
            $storagePid = $configuration['settings']['storagePid'];
            if ($storagePid === null) {
                throw new Exception('Missing TYPOSCRIPT: plugin.tx_translatelabels.settings.storagePid not defined.', 1567012007);
            }
        }
        return $storagePid;
    }

    /**
     * @param string $labelKey Translation Key compatible to TYPO3 Flow
     * @param string $fallBackTranslation  current translation from LocalizationUtility::translate
     *                                     (will be used if no record is found)
     * @return string                      new translation, maybe overridden from translation record if defined
     * @throws Exception
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
     */
    /**
     * @param string $labelKey
     * @param int $pid
     * @param int|null $languageUid
     * @return object
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
     * @param string $labelKey
     * @param int $translation
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
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
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectNotFoundException
     */
    public static function isFrontendWithLoggedInBEUser($labelKey = '', $extensionName = '')
    {
        $showTranslationLabels = false;

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
            && TYPO3_MODE === 'FE'
            && strpos($labelKey, 'LLL:EXT:adminpanel') !== 0
            && $extensionName !== 'adminpanel'
            && $showTranslationLabels === '1'
            // only for uncached pages as no_cache is set by admin panel
            // otherwise we could generate LLL tags into cached pages and will not replace them
            // in FE if no BE user is logged-in.
            && $GLOBALS['TSFE']->no_cache === true);
    }

    /**
     * @param $extensionName
     * @return string
     */
    public static function getDefaultLanguageFile($extensionName) {
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
        $languageFile = strrev($reversedParts[1]);
        $extendedLabelKey = $labelKey;
        if ($languageFile === '') {
            // $extendedLabelKey = 'LLL:EXT:' . self::getDefaultLanguageFile($extensionName) . ':' . $labelKey;
            $extendedLabelKey = self::getDefaultLanguageFile($extensionName) . ':' . $labelKey;
        }
        return $extendedLabelKey;
    }

    public static function getLabelKeyWithoutPrefixes($labelKey)
    {
        // remove leading 'LLL:'
        $labelKey = (strpos($labelKey,'LLL:') === 0) ? substr($labelKey, 4) : $labelKey;
        // remove leading 'EXT:'
        $labelKey = (strpos($labelKey, 'EXT:') === 0) ? substr($labelKey, 4) : $labelKey;
        return $labelKey;
    }
}
