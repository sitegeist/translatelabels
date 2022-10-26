<?php
declare(strict_types=1);

namespace Sitegeist\Translatelabels\Controller;

/**
 *
 * This file is part of the "translatelabels" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 *
 */
use function GuzzleHttp\json_decode;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Adminpanel\Service\ConfigurationService;
use TYPO3\CMS\Adminpanel\Service\ModuleLoader;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use Sitegeist\Translatelabels\Utility\TranslationLabelUtility;

/**
 * Admin Panel Ajax Controller - Route endpoint for ajax actions
 *
 * @internal
 */
class AjaxController
{
    /**
     * @var array
     */
    protected $adminPanelModuleConfiguration;

    /**
     * @var ModuleLoader
     */
    protected $moduleLoader;

    /**
     * @var ConfigurationService
     */
    private $configurationService;

    /**
     * @var string
     */
    protected $tableName = 'tx_translatelabels_domain_model_translation';

    /**
     * @var LanguageService
     */
    protected $languageService;

    /**
     * @param ConfigurationService $configurationService
     * @param ModuleLoader $moduleLoader
     */
    public function __construct(ConfigurationService $configurationService = null, ModuleLoader $moduleLoader = null)
    {
        $this->configurationService = $configurationService
            ??
            GeneralUtility::makeInstance(ConfigurationService::class);
        $this->adminPanelModuleConfiguration = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['adminpanel']['modules'] ?? [];
        $this->moduleLoader = $moduleLoader ?? GeneralUtility::makeInstance(ModuleLoader::class);
        $this->languageService = GeneralUtility::makeInstance(LanguageService::class);
        $this->languageService->init($this->getBackendUser()->uc['lang']);
    }

    /**
     * save/create the translation
     *
     * request is in json format:
     * {
     *  "key":                "LLL:EXT:mysysmex_site/Resources/Private/Language/Global/global.xlf:layout.default.cookieConsentBannerMessage",
     *  "value":              "We are using cookies on this website. We assume your consent, as you are making use of this.",
     *  "currentTranslation": "We are using cookies on this website. We assume your consent, as you are making use of this.",
     *  "sysLanguageUid":     "24",
     *  "storagePid:          "342"
     * }
     *
     * @param ServerRequestInterface $request
     * @return JsonResponse
     */
    public function translateAction(ServerRequestInterface $request): JsonResponse
    {
        try {
            $data = json_decode($request->getBody()->getContents());
            $this->checkPermissions($data);
            $context = GeneralUtility::makeInstance(Context::class);
            $successMessage = $this->languageService->sL('LLL:EXT:translatelabels/Resources/Private/Language/locallang_adminpanel.xlf:sub.module.ajax.success');
            $backendUserId = $context->getPropertyFromAspect('backend.user', 'id');

            $translation = $this->findTranslation($data->key, (int)$data->storagePid, (int)$data->sysLanguageUid);
            if ($translation !== false) {
                if ($translation['translation'] !== $data->value) {
                    // translation exists
                    $this->updateTranslation($translation['uid'], TranslationLabelUtility::stripAllTagsButNewlines($data->value));
                    return new JsonResponse(['status' => 'ok', 'message' => $successMessage, 'action' => 'changed', 'uid' => $translation['uid']]);
                }
            } else {
                if ((int)$data->sysLanguageUid !== 0) {
                    // find l10n_parent
                    $translationInDefaultLanguage = $this->findTranslation($data->key, (int)$data->storagePid, 0);
                    if ($translationInDefaultLanguage === false) {
                        // create l10n_parent, fetch translation from language file
                        // if no translation exists from language file then use printed translation from frontend
                        $defaultTranslation = $this->getTranslationFromDefaultLanguage($data->key) ?: $data->currentTranslation;
                        $uidOfTranslationInDefaultLanguage = $this->insertTranslation([
                            'translation' => $defaultTranslation,
                            'labelkey' => $data->key,
                            'pid' => $data->storagePid,
                            'sys_language_uid' => 0,
                            'cruser_id' => $backendUserId
                        ]);
                    } else {
                        $uidOfTranslationInDefaultLanguage = $translationInDefaultLanguage['uid'];
                    }
                } else {
                    // new translation to be created in default language
                    $uidOfTranslationInDefaultLanguage = 0;
                }
                // create translation related to l10n_parent
                $this->insertTranslation([
                    'translation' => TranslationLabelUtility::stripAllTagsButNewlines($data->value),
                    'labelkey' => $data->key,
                    'pid' => $data->storagePid,
                    'sys_language_uid' => $data->sysLanguageUid,
                    'l10n_parent' => $uidOfTranslationInDefaultLanguage,
                    'tstamp' => time(),
                    'crdate' => time(),
                    'cruser_id' => $backendUserId
                ]);

                // no translation record found => create one
                return new JsonResponse(['status' => 'ok', 'message' => $successMessage, 'action' => 'new']);
            }
            return new JsonResponse(['status' => 'ok', 'message' => $successMessage, 'action' => 'unmodified', 'uid' => $translation['uid']]);
        } catch (\Exception $e) {
            return new JsonResponse(['status' => 'error', 'message' => $e->getMessage(), 'code' => $e->getCode()]);
        }
    }

    protected function getTranslationFromDefaultLanguage($translationKey)
    {
        if (strpos($translationKey, 'LLL:EXT:') !== 0) {
            $translationKey = 'LLL:EXT:' . $translationKey;
        }

        $translationInDefaultLanguage = LocalizationUtility::translate(
            $translationKey,
            null,
            null,
            'default'
        );

        return $translationInDefaultLanguage;
    }

    /**
     * Returns the current BE user.
     *
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @param string $labelKey
     * @param int $pid
     * @param int $sysLanguageUid
     * @return mixed
     */
    protected function findTranslation(string $labelKey, int $pid, int $sysLanguageUid = 0)
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class);
        $connectionForTranslations = $connection->getConnectionForTable($this->tableName);

        $translation = $connectionForTranslations
            ->select(
                ['uid', 'pid', 'labelkey', 'translation'], // fields to select
                $this->tableName, // from
                [
                    'labelkey' => $labelKey,
                    'pid' => $pid,
                    'sys_language_uid' => $sysLanguageUid
                ] // where
            )
            ->fetch();

        return $translation;
    }

    /**
     * @param array $fields
     * @return string
     */
    protected function insertTranslation(array $fields)
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class);
        $connectionForTranslations = $connection->getConnectionForTable($this->tableName);
        $queryBuilder = $connection->getQueryBuilderForTable($this->tableName);
        $fields['crdate'] = $fields['crdate'] ?? time();
        $fields['tstamp'] = $fields['tstamp'] ?? time();
        $queryBuilder
            ->insert($this->tableName)
            ->values($fields)
            ->execute();
        return $connectionForTranslations->lastInsertId($this->tableName);
    }

    /**
     * @param int $uid
     * @param string $translation
     */
    protected function updateTranslation(int $uid, string $translation): void
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class);
        $connectionForTranslations = $connection->getConnectionForTable($this->tableName);
        $connectionForTranslations->update(
            $this->tableName,
            [
                'translation' => $translation,
                'tstamp' => time()
            ],
            [
                'uid' => $uid
            ] // where
        );
    }

    /**
     * @param $data
     * @throws \Exception
     */
    protected function checkPermissions($data): void
    {
        if (!$this->getBackendUser()->check('tables_modify', $this->tableName)) {
            throw new \Exception('BE user is not allowed to edit translations', 1568803075);
        }
        if (!$this->getBackendUser()->checkLanguageAccess($data->sysLanguageUid)) {
            throw new \Exception('BE user is not allowed to edit current language', 1568805243);
        }
        if (!$this->getBackendUser()->isInWebMount($data->storagePid)) {
            throw new \Exception('BE user is not edit translations in sysfolder with uid:' . $data->storagePid, 1568806004);
        }

        $page = BackendUtility::getRecord('pages', $data->storagePid, '*');
        if (($this->getBackendUser()->calcPerms($page) & Permission::CONTENT_EDIT) !== Permission::CONTENT_EDIT) {
            throw new \Exception('BE user is not edit translations in sysfolder with uid:' . $data->storagePid, 1568806554);
        }
    }
}
