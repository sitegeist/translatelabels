<?php
declare(strict_types = 1);
namespace Sitegeist\Translatelabels\ViewHelpers\Traits;

/**
 *
 * This file is part of the "translatelabels" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 *
 */
use TYPO3\CMS\Core\Http\ApplicationType;
use Sitegeist\Translatelabels\Utility\TranslationLabelUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3\CMS\Form\ViewHelpers\RenderRenderableViewHelper;

trait RenderTranslation
{
    /**
     * If the array contains numerical keys only, sort it in descending order
     *
     * @param array $array
     * @return array
     */
    protected static function sortArrayWithIntegerKeysDescending(array $array)
    {
        if (\count(array_filter(array_keys($array), 'is_string')) === 0) {
            krsort($array);
        }
        return $array;
    }

    public static function renderTranslation(string $translationKey, string $value, array $translateArguments)
    {

        if (ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isFrontend() && $value !== '') {
            // language files in chain of form framework are without LLL:, all others use this prefix
            // $id = (strpos($translationKey, 'LLL:') === 0) ? $translationKey : 'LLL:' . $translationKey;
            $id = TranslationLabelUtility::getLabelKeyWithoutPrefixes($translationKey);
            $value = TranslationLabelUtility::readLabelFromDatabase($id, $value);
            if (\is_array($translateArguments) && count($translateArguments) > 0 && $value !== null) {
                $value = sprintf($value, ...array_values($translateArguments)) ?: sprintf(
                    'Error: could not translate key "%s" with value "%s" and %d argument(s)!',
                    $translationKey,
                    $value,
                    count($translateArguments)
                );
            }
            if (TranslationLabelUtility::isFrontendWithLoggedInBEUser($id)) {
                $value = TranslationLabelUtility::renderTranslationWithExtendedInformation(
                    $id,
                    $value,
                    'form',
                    $translateArguments
                );
            }
        }
        return $value;
    }

    public static function getHighestOrderLanguageFile(RenderingContextInterface $renderingContext)
    {
        /** @var TYPO3\CMS\Form\FormRuntime $formRuntime */
        $formRuntime = $renderingContext
            ->getViewHelperVariableContainer()
            ->get(RenderRenderableViewHelper::class, 'formRuntime');
        $languageFiles = $formRuntime->getRenderingOptions()['translation']['translationFiles'];
        if (\is_string($languageFiles)) {
            $highestOrderLanguageFile = $languageFiles;
        } else {
            $highestOrderLanguageFile = array_pop(
                array_reverse(self::sortArrayWithIntegerKeysDescending($languageFiles))
            );
        }
        return $highestOrderLanguageFile;
    }
}
