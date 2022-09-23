<?php
declare(strict_types = 1);
namespace Sitegeist\Translatelabels\ViewHelpers;

/**
 *
 * This file is part of the "translatelabels" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 *
 */

use TYPO3\CMS\Extbase\Error\Error;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;
use Sitegeist\Translatelabels\ViewHelpers\Traits\RenderTranslation;
use TYPO3\CMS\Form\ViewHelpers\RenderRenderableViewHelper;

/**
 * Class TranslateElementErrorViewHelper
 * @package Sitegeist\Translatelabels\ViewHelpers
 *
 * extends the base translateElementError viewhelper to show names of translation labels instead of translations
 * if admin panel is activated and checkbox "show translation labels" is checked.
 */
class TranslateElementErrorViewHelper extends \TYPO3\CMS\Form\ViewHelpers\TranslateElementErrorViewHelper
{
    use CompileWithRenderStatic;
    use RenderTranslation;

    /**
     * Return array element by key.
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return string
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $originalTranslation = parent::renderStatic($arguments, $renderChildrenClosure, $renderingContext);

        $element = $arguments['element'];
        $error = $arguments['error'];

        $code = $arguments['code'];
        $errorArguments = $arguments['arguments'];
        $defaultValue = $arguments['defaultValue'];

        if ($error instanceof Error) {
            $code = $error->getCode();
            $errorArguments = $error->getArguments();
            $defaultValue = $error->__toString();
        } else {
            trigger_error(
                'TranslateElementErrorViewHelper arguments "code", "arguments" and "defaultValue" will be removed in TYPO3 v10.0. Use "error" instead.',
                E_USER_DEPRECATED
            );
        }

        /** @var TYPO3\CMS\Form\FormRuntime $formRuntime */
        $formRuntime = $renderingContext
            ->getViewHelperVariableContainer()
            ->get(RenderRenderableViewHelper::class, 'formRuntime');

        $originalFormIdentifier = $formRuntime->getRenderingOptions()['_originalIdentifier'] ?? $formRuntime->getIdentifier();

        $translationKey = sprintf('%s:%s.validation.error.%s.%s', self::getHighestOrderLanguageFile($renderingContext), $originalFormIdentifier, $element->getIdentifier(), $code);
        if ($originalTranslation === '') {
            $originalTranslation = $defaultValue;
        }
        return self::renderTranslation($translationKey, $originalTranslation, $errorArguments);
    }
}
