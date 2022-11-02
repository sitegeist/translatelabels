<?php

namespace Sitegeist\Translatelabels\Hooks;

/**
 *
 * This file is part of the "translatelabels" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 *
 */
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Sitegeist\Translatelabels\Renderer\FrontendRenderer;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use Sitegeist\Translatelabels\Exception\LabelReplaceException;
use Sitegeist\Translatelabels\Utility\TranslationLabelUtility;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;

/**
 * Class TypoScriptFrontendController
 * Used for Hook $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output']
 *
 * @package Sitegeist\Translatelabels\Hooks
 */
class TypoScriptFrontendController
{
    /**
     * Search for LLL:("<translation>","<key>",...) tags and replace them with a tooltip markup
     * but only if a be user is logged in
     *
     * @param array $params
     * @param \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $ref
     * @throws \TYPO3\CMS\Backend\Exception
     * @throws RouteNotFoundException
     * @throws AspectNotFoundException
     */
    public function contentPostProcAll(
        array &$params,
        \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $ref
    ) {
        if ($ref->isINTincScript() || !TranslationLabelUtility::isFrontendWithLoggedInBEUser()) {
            return;
        }

        $sysFolderWithTranslationsUid = TranslationLabelUtility::getStoragePid();
        if ($sysFolderWithTranslationsUid === null) {
            throw new LabelReplaceException('TSFE.constants.plugin.tx_translatelabels.settings.storagePid not set in page TSconfig', 1543243652);
        }

        /** @var FrontendRenderer $frontendRenderer */
        $frontendRenderer = GeneralUtility::makeInstance(FrontendRenderer::class);

        // store all labels in T3_VAR to show them in admin panel later on
        $GLOBALS['TRANSLATELABELS'] = $frontendRenderer->parseLabelTags($ref->content, $sysFolderWithTranslationsUid);

        // step 1
        // substitute all labels inside of tag attributes
        // finds one or more labels inside of tag attribute values
        // search for all tags with at least one LLL marker and add a data attribute
        $ref->content = $frontendRenderer->substituteLabelsInsideTags($ref->content);

        // step 2
        // substitute all remaining LLL markers with markup to show a tooltip with name of label
        // as this is step 2 only LLL markers outside of tags should be affected
        $message = $ref->sL('LLL:EXT:translatelabels/Resources/Private/Language/locallang_adminpanel.xlf:tooltip.message');
        $ref->content = $frontendRenderer->substituteLabels($ref->content, $message);
    }
}
