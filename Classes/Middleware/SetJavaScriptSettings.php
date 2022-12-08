<?php

/**
 *
 * This file is part of the "translatelabels" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 *
 */

namespace Sitegeist\Translatelabels\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Adminpanel\Utility\StateUtility;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Middleware to avoid spinner after loading BE form.
 */
class SetJavaScriptSettings implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (StateUtility::isActivatedForUser() && StateUtility::isOpen()) {
            $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
            $pageRenderer->addInlineSetting('ContextHelp', 'moduleUrl', '');
            $dateFormat = ($GLOBALS['TYPO3_CONF_VARS']['SYS']['USdateFormat'] ? ['MM-DD-YYYY', 'HH:mm MM-DD-YYYY'] : ['DD-MM-YYYY', 'HH:mm DD-MM-YYYY']);
            $pageRenderer->addInlineSetting('DateTimePicker', 'DateFormat', $dateFormat);
        }
        return $handler->handle($request);
    }
}
