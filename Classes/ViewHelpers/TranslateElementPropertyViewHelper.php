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

use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3\CMS\Form\ViewHelpers\RenderRenderableViewHelper;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\Exception\MissingArrayPathException;
use TYPO3\CMS\Form\Domain\Model\FormElements\FormElementInterface;
use Sitegeist\Translatelabels\ViewHelpers\Traits\RenderTranslation;

/**
 * Class TranslateElementPropertyViewHelper
 * @package Sitegeist\Translatelabels\ViewHelpers
 *
 * extends the base translateElementProperty viewhelper to show names of translation labels instead of translations
 * if admin panel is activated and checkbox "show translation labels" is checked.
 *
 * Adds a special behavior if attribute "property" is an array with first item = 'items' and second item as
 * array with key,value pairs. This renders translation labels for each item in the key,value array.
 * This can be used for lists of values used in components created with extension fluid_components to transfer
 * them into compound components.
 * Example for usage:
 * {formvh:translateElementProperty(element: element, property: '{0: \'items\', 1: element.properties.options}')}
 * element.properties.options is for example:
 * array(4 items)
 *    First => 'erste'
 *    Second => 'zweite'
 *    Third => 'dritte'
 *    Fourth => 'vierte'
 */
class TranslateElementPropertyViewHelper extends \TYPO3\CMS\Form\ViewHelpers\TranslateElementPropertyViewHelper
{

    use RenderTranslation;

    protected static function getPropertyName($property)
    {
        return \is_array($property) ? implode('.', $property) : $property;
    }

    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $element = $arguments['element'];

        $renderingOptions = $element->getRenderingOptions();

        $property = null;
        $propertyType = 'properties';

        if (!empty($arguments['property'])) {
            $property = $arguments['property'];
        } elseif (!empty($arguments['renderingOptionProperty'])) {
            // renderingOptionProperty is used f.e. in public/typo3/sysext/form/Resources/Private/Frontend/Partials/Form/Navigation.html
            // f.e. {formvh:translateElementProperty(element: form, renderingOptionProperty: 'submitButtonLabel')}
            // possible values are: 'previousButtonLabel' | 'nextButtonLabel' | 'submitButtonLabel'
            $property = $arguments['renderingOptionProperty'];
        }

        if (empty($property)) {
            $propertyParts = [];
        } elseif (\is_array($property)) {
            $propertyParts = $property;
        } else {
            $propertyParts = [$property];
        }

        if ($property === 'label') {
            $defaultValue = $element->getLabel();
        } else {
            if ($element instanceof FormElementInterface) {
                try {
                    $defaultValue = ArrayUtility::getValueByPath($element->getProperties(), $propertyParts, '.');
                } catch (MissingArrayPathException $exception) {
                    $defaultValue = null;
                }
            } else {
                $propertyType = 'renderingOptions';
                try {
                    $defaultValue = ArrayUtility::getValueByPath($renderingOptions, $propertyParts, '.');
                } catch (MissingArrayPathException $exception) {
                    $defaultValue = null;
                }
            }
        }

        /** @var TYPO3\CMS\Form\FormRuntime $formRuntime */
        $formRuntime = $renderingContext
            ->getViewHelperVariableContainer()
            ->get(RenderRenderableViewHelper::class, 'formRuntime');
        $highestOrderLanguageFile = self::getHighestOrderLanguageFile($renderingContext);

        $originalFormIdentifier = $formRuntime->getRenderingOptions()['_originalIdentifier'] ?? null;
        // here we have to decide wether to use a label for all instances of the form definition or only
        // for the active form shown in FE (one instance)
        // by default we generate labels for all instances
        //
        // The editor could simply change the label to create more specific labels for only the current one instance
        // of one form template.
        // To do so he has to rename the label from
        // LLL:EXT:myext/Resources/Private/Language/Form/locallang.xlf:formname-uid.element.text-3.properties.label
        // to
        // LLL:EXT:myext/Resources/Private/Language/Form/locallang.xlf:formname.element.text-3.properties.label

        // $formIdentifier = $formRuntime->getIdentifier(); // <formname>-<pluginUid>
        $formIdentifier = $originalFormIdentifier; // <formname>
        $translationKey = sprintf(
            '%s:%s.element.%s.%s.%s',
            $highestOrderLanguageFile,
            $formIdentifier,
            $element->getIdentifier(),
            'properties',
            self::getPropertyName($property)
        );
        try {
            $translationArguments = ArrayUtility::getValueByPath(
                $element->getRenderingOptions()['translation']['arguments'] ?? [],
                $propertyParts,
                '.'
            );
        } catch (MissingArrayPathException $e) {
            $translationArguments = [];
        }
        $ret = parent::renderStatic($arguments, $renderChildrenClosure, $renderingContext);
        if ($property === 'label' ||
            $property === 'elementDescription' ||
            // $property === 'submitButtonLabel' ||
            $property === 'fluidAdditionalAttributes' ||
            (\is_array($property) && $property[0] === 'options')
        ) {
            if (\is_array($ret)) {
                foreach ($ret as $key => $value) {
                    $ret[$key] = self::renderTranslation(
                        $translationKey . '.' . $key,
                        (string)$value,
                        $translationArguments
                    );
                }
            } else {
                $ret = self::renderTranslation($translationKey, $ret, $translationArguments);
            }
        } elseif (\is_array($property) && $property[0] === 'items') {
            // new parameter: options is array auf key-value pairs
            $ret = [];
            foreach ($property[1] as $key => $value) {
                $translationKey = sprintf(
                    '%s:%s.element.%s.%s.%s.%s',
                    $highestOrderLanguageFile,
                    $formIdentifier,
                    $element->getIdentifier(),
                    'properties',
                    'options',
                    $key
                );
                $ret[$key] = self::renderTranslation($translationKey, $value, $translationArguments);
            }
        } elseif ($propertyType === 'renderingOptions') {
            // for buttons, $property can be 'previousButtonLabel' or 'nextButtonLabel' or 'submitButtonLabel'
            // element.register-168.renderingOptions.submitButtonLabel
            $translationKey = sprintf(
                '%s:element.%s.%s.%s',
                $highestOrderLanguageFile,
                $formIdentifier,
                $propertyType,
                $property
            );
            $ret = self::renderTranslation($translationKey, $ret, $translationArguments);
        }
        return $ret;
    }
}
