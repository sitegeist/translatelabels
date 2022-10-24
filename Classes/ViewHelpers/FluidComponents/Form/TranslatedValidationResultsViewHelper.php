<?php
namespace Sitegeist\Translatelabels\ViewHelpers\FluidComponents\Form;

/**
 *
 * This file is part of the "translatelabels" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 *
 */
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Form\Domain\Model\Renderable\RootRenderableInterface;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;
use TYPO3\CMS\Form\Service\TranslationService;
use TYPO3\CMS\Form\ViewHelpers\RenderRenderableViewHelper;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;
use Sitegeist\Translatelabels\ViewHelpers\Traits\RenderTranslation;
use Sitegeist\Translatelabels\Utility\TranslationLabelUtility;

/**
 * @package Sitegeist\Translatelabels\ViewHelpers\Form
 * @author Alexander Bohndorf <bohndorf@sitegeist.de>
 */
class TranslatedValidationResultsViewHelper extends \SMS\FluidComponents\ViewHelpers\Form\TranslatedValidationResultsViewHelper
{
    use CompileWithRenderStatic;
    use RenderTranslation;

    /**
     * @param array $translationChain
     * @param int $code
     * @param array $arguments The arguments of the extension, being passed over to vsprintf
     * @param string $defaultValue
     * @param string|null $extensionName The name of the extension
     * @param string $languageKey The language key or null for using the current language from the system
     * @param string[] $alternativeLanguageKeys The alternative language keys if no translation was found. If null and we are in the frontend, then the language_alt from TypoScript setup will be used
     * @return string|null The value from LOCAL_LANG or null if no translation was found.
     */
    public static function translateValidationError(
        array $translationChain,
        int $code,
        array $arguments,
        string $defaultValue = '',
        string $extensionName = null,
        string $languageKey = null,
        array $alternativeLanguageKeys = null
    ): ?string {
        foreach ($translationChain as $translatePrefix) {
            $translatedMessage = LocalizationUtility::translate(
                $translatePrefix . $code,
                $extensionName,
                $arguments,
                $languageKey,
                $alternativeLanguageKeys
            );
            if ($translatedMessage) {
                break;
            }
        }
        $translatedMessage = $translatedMessage ?? $defaultValue;
        $translationKey = TranslationLabelUtility::getExtendLabelKeyWithLanguageFilePath(reset($translationChain) . $code, $extensionName);
        return self::renderTranslation($translationKey, $translatedMessage, $arguments);
    }

    /**
     * Translates the provided validation message by using the translation chain by EXT:form
     *
     * @param RenderingContextInterface $renderingContext
     * @param RootRenderableInterface $element
     * @param int $code
     * @param string $defaultValue
     * @param array $arguments
     * @return string
     * @throws \InvalidArgumentException
     */
    public static function translateFormElementError(
        RenderingContextInterface $renderingContext,
        RootRenderableInterface $element,
        int $code,
        array $arguments,
        string $defaultValue = ''
    ): string {
        /** @var FormRuntime $formRuntime */
        $formRuntime = $renderingContext
            ->getViewHelperVariableContainer()
            ->get(RenderRenderableViewHelper::class, 'formRuntime');

        $originalTranslation = GeneralUtility::makeInstance(TranslationService::class)->translateFormElementError(
            $element,
            $code,
            $arguments,
            $defaultValue,
            $formRuntime
        );

        $originalFormIdentifier = $formRuntime->getRenderingOptions()['_originalIdentifier'] ?? $formRuntime->getIdentifier();

        $translationKey = sprintf(
            '%s:%s.validation.error.%s.%s',
            self::getHighestOrderLanguageFile($renderingContext),
            $originalFormIdentifier,
            $element->getIdentifier(),
            $code
        );
        if ($originalTranslation === '') {
            $originalTranslation = $defaultValue;
        }
        return self::renderTranslation($translationKey, $originalTranslation, $arguments);
    }
}
