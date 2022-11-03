<?php

/*
 * This file is part of the package jweiland/replacer.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Sitegeist\Translatelabels\Middleware;

use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Http\NullResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sitegeist\Translatelabels\Renderer\FrontendRenderer;
use Sitegeist\Translatelabels\Exception\LabelReplaceException;
use Sitegeist\Translatelabels\Utility\TranslationLabelUtility;

/**
 * Middleware to replace content using TSFE.
 * Will be used for pages with USER_INT plugins only!
 * Otherwise Hooks\TypoScriptFrontendController will replace the content.
 */
class ReplaceLabels implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        if (!$GLOBALS['TSFE']->isINTincScript() || $response instanceof NullResponse || !TranslationLabelUtility::isFrontendWithLoggedInBEUser()) {
            return $response;
        }

        $sysFolderWithTranslationsUid = TranslationLabelUtility::getStoragePid();
        if ($sysFolderWithTranslationsUid === null) {
            throw new LabelReplaceException('TSFE.constants.plugin.tx_translatelabels.settings.storagePid not set in page TSconfig', 1543243652);
        }

        $content = (string)$response->getBody();

        /** @var FrontendRenderer $frontendRenderer */
        $frontendRenderer = GeneralUtility::makeInstance(FrontendRenderer::class);

        // store all labels in T3_VAR to show them in admin panel later on
        $GLOBALS['TRANSLATELABELS'] = $frontendRenderer->parseLabelTags($content, $sysFolderWithTranslationsUid);

        // step 1
        // substitute all labels inside of tag attributes
        // finds one or more labels inside of tag attribute values
        // search for all tags with at least one LLL marker and add a data attribute
        $content = $frontendRenderer->substituteLabelsInsideTags($content);

        // step 2
        // substitute all remaining LLL markers with markup to show a tooltip with name of label
        // as this is step 2 only LLL markers outside of tags should be affected
        $message = $GLOBALS['TSFE']->sL('LLL:EXT:translatelabels/Resources/Private/Language/locallang_adminpanel.xlf:tooltip.message');
        $content = $frontendRenderer->substituteLabels($content, $message);

        $body = new Stream('php://temp', 'rw');
        $body->write($content);

        return $response->withBody($body);
    }
}
