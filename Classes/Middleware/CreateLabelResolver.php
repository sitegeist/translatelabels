<?php
declare(strict_types = 1);
namespace Sitegeist\Translatelabels\Middleware;

/**
 *
 * This file is part of the "translatelabels" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 *
 */
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\Uri;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use Sitegeist\Translatelabels\Utility\TranslationLabelUtility;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Backend\Routing\UriBuilder;

class CreateLabelResolver implements MiddlewareInterface
{
    /**
     * creates translation records in default language and localizations of them on the fly to give
     * editors more comfort in editing translations
     *
     * only active on route /record/edit with get param ['tx_translatelabels']['key']
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws RouteNotFoundException
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $queryParams = $request->getQueryParams();
        if (\is_array($queryParams) &&
            isset($queryParams['route']) &&
            $queryParams['route'] === '/record/edit' &&
            isset($queryParams['tx_translatelabels']) &&
            \is_array($queryParams['tx_translatelabels']) &&
            isset($queryParams['tx_translatelabels']['key'])
        ) {
            $uidOfNewTranslationInDefaultLanguage = null;
            $translatedLabelRecord = TranslationLabelUtility::getLabelFromDatabase(
                $queryParams['tx_translatelabels']['key'],
                (int)$queryParams['tx_translatelabels']['pid'],
                (int)$queryParams['tx_translatelabels']['language']
            );
            $labelRecordInDefaultLanguage = TranslationLabelUtility::getLabelFromDatabase(
                $queryParams['tx_translatelabels']['key'],
                (int)$queryParams['tx_translatelabels']['pid'],
                0
            );
            $dataHandler = GeneralUtility::makeInstance(
                DataHandler::class
            );
            if ($labelRecordInDefaultLanguage === null) {
                // create record in default language on the fly
                $translationKey = $queryParams['tx_translatelabels']['key'];
                if (strpos($translationKey, 'LLL:EXT:') !== 0) {
                    $translationKey = 'LLL:EXT:' . $translationKey;
                }

                $translationInDefaultLanguage = LocalizationUtility::translate(
                    $translationKey,
                    null,
                    null,
                    'default'
                ) ?: $queryParams['tx_translatelabels']['translation'];
                $data['tx_translatelabels_domain_model_translation']['NEW_1'] = [
                    'pid' => $queryParams['tx_translatelabels']['pid'],
                    'sys_language_uid' => 0,
                    'labelkey' => $queryParams['tx_translatelabels']['key'],
                    'translation' => $translationInDefaultLanguage
                ];
                $dataHandler->start($data, []);
                $dataHandler->process_datamap();

                $uidOfTranslationInDefaultLanguage = $dataHandler->substNEWwithIDs['NEW_1'];
            } else {
                $uidOfTranslationInDefaultLanguage = $labelRecordInDefaultLanguage->getUid();
            }

            if ((int)$queryParams['tx_translatelabels']['language'] === 0) {
                // go to translation record in default language
                return new RedirectResponse(
                    $this->getLinkToEditTranslation(
                        $queryParams['tx_translatelabels']['pid'],
                        $uidOfTranslationInDefaultLanguage
                    ),
                    307
                );
            }

            if ($translatedLabelRecord === null && (int)$queryParams['tx_translatelabels']['language'] !== 0) {
                // translation record in default language exists but localization has to be created now
                $cmd['tx_translatelabels_domain_model_translation'][$uidOfTranslationInDefaultLanguage]['localize'] =
                    $queryParams['tx_translatelabels']['language'];
                $dataHandler->start([], $cmd);
                $dataHandler->process_cmdmap();
                // fetch uid of created localization because dataHandler doesn't return the new uid :-(
                $translatedLabelRecord = TranslationLabelUtility::getLabelFromDatabase(
                    $queryParams['tx_translatelabels']['key'],
                    (int)$queryParams['tx_translatelabels']['pid'],
                    (int)$queryParams['tx_translatelabels']['language']
                );

                // change field translation from default copy during localization to given translation via get param
                unset($data);
                $data['tx_translatelabels_domain_model_translation'][$translatedLabelRecord->getUid()] = [
                    'translation' => $queryParams['tx_translatelabels']['translation']
                ];
                $dataHandler->start($data, []);
                $dataHandler->process_datamap();

                return new RedirectResponse(
                    $this->getLinkToEditTranslation(
                        $queryParams['tx_translatelabels']['pid'],
                        $translatedLabelRecord->getUid()
                    ),
                    307
                );
            } else {
                // translation record in default language exists and localization exists also, so let's
                // just redirect to edit the localization
                return new RedirectResponse(
                    $this->getLinkToEditTranslation(
                        $queryParams['tx_translatelabels']['pid'],
                        $translatedLabelRecord->getUid()
                    ),
                    307
                );
            }
        }
        // Invoke inner middlewares and eventually the TYPO3 kernel
        return $handler->handle($request);
    }

    /**
     * Generates link to BE module to create new translation record
     *
     * @param $sysFolderWithTranslationsUid
     * @param $labelKey
     * @param $translation
     * @return Uri
     * @throws RouteNotFoundException
     */
    protected function getLinkToBEForNewTranslation($sysFolderWithTranslationsUid, $labelKey, $translation)
    {
        $uri = $this->getLinkToBEModule('record_edit', [
            'id' => $sysFolderWithTranslationsUid,
            'edit[tx_translatelabels_domain_model_translation][' . $sysFolderWithTranslationsUid . ']' => 'new',
            'defVals[tx_translatelabels_domain_model_translation][labelkey]' => $labelKey,
            'defVals[tx_translatelabels_domain_model_translation][translation]' => $translation,
            'returnUrl' => (string)$this->getLinkToBEModule('web_list', ['id' => $sysFolderWithTranslationsUid])
        ]);
        return $uri;
    }

    /**
     * Generates link to BE module to create new localization of translation record
     *
     * @param $sysFolderWithTranslationsUid
     * @param $translationUid
     * @param $sysLanguageUid
     * @return Uri
     * @throws RouteNotFoundException
     */
    protected function getLinkToBEForNewLocalizationOfTranslation($sysFolderWithTranslationsUid, $translationUid, $sysLanguageUid)
    {
        $uri = $this->getLinkToBEModule('/record/commit', [
            'cmd[tx_translatelabels_domain_model_translation][' . $translationUid . '][localize]' => $sysLanguageUid,
            'redirect' => (string)$this->getLinkToBEModule('/record/edit', [
                'id' => $translationUid,
                'justLocalized' =>
                    'tx_translatelabels_domain_model_translation:' .
                    $translationUid .
                    ':' .
                    $sysLanguageUid,
                'returnUrl' => (string)$this->getLinkToBEModule('web_list', [
                    'id' => $sysFolderWithTranslationsUid,
                    'imagemode' => 1,
                    'table' => 'tx_translatelabels_domain_model_translation'
                ])
            ])
        ]);
        return $uri;
    }

    /**
     * @param $sysFolderWithTranslationsUid
     * @param $translationUid
     * @return Uri
     * @throws RouteNotFoundException
     */
    protected function getLinkToEditTranslation($sysFolderWithTranslationsUid, $translationUid)
    {
        $uri = $this->getLinkToBEModule('/record/edit', [
            'edit' => [
                'tx_translatelabels_domain_model_translation' => [
                    $translationUid => 'edit'
                ]
            ],
            'returnUrl' => (string)$this->getLinkToBEModule('web_list', [
                'id' => $sysFolderWithTranslationsUid,
                'table' => 'tx_translatelabels_domain_model_translation',
                'imagemode' => 1,
                ])
        ]);
        return $uri;
    }

    /**
     * returns an uri to the backend module defined by $route, f.e. 'web_list' with
     * the given parameters
     *
     * @param $route
     * @param $urlParameters
     * @return Uri
     * @throws RouteNotFoundException
     */
    protected function getLinkToBEModule($route, $urlParameters)
    {
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        try {
            $uri = $uriBuilder->buildUriFromRoute($route, $urlParameters);
        } catch (RouteNotFoundException $e) {
            $uri = $uriBuilder->buildUriFromRoutePath($route, $urlParameters);
        }
        return $uri;
    }
}
