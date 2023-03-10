<?php

declare(strict_types=1);

namespace Sitegeist\Translatelabels\Renderer;

use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use Sitegeist\Translatelabels\Exception;
use Sitegeist\Translatelabels\Utility\TranslationLabelUtility;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Context\Context;
use Psr\Http\Message\ServerRequestInterface;

/**
 *
 * This file is part of the "translatelabels" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 *
 */

class FrontendRenderer
{

    // single quote ' is escaped in fluid inside of tag attributes to &#039;
    // so we have to look out for single quotes as either ' or &#039;
    // a regex group starting with (?: instead of ( is a non-capturing group
    const REGEX_QUOTE = '(?:&#039;|\')'; // delimiter for parameters => has to be same as defined in TranslationLabelUtility::renderTranslationWithExtendedInformation
    const REGEX_PARAMETERVALUE = '(.*?)';

    /**
     * returns an array of label objects parsed from $content
     *
     * @param string $content
     * @param int $sysFolderWithTranslationsUid
     * @return array
     * @throws RouteNotFoundException
     * @throws AspectNotFoundException
     */
    public function parseLabelTags(string $content, int $sysFolderWithTranslationsUid) : array
    {
        $labelTags = [];
        if (preg_match_all(self::getRegexForLLLMarker(), $content, $matches)) {
            $countLabels = \count($matches[0]);
            $keys = [];
            for ($i = 0; $i < $countLabels; $i++) {
                // clean quoting of slashes from json encoding in labels in attribute values
                $currentKey = str_replace('\/', '/', $matches[2][$i]);
                if (!in_array($currentKey, $keys)) { // each label/key only once!
                    $labelTags[] = [
                        'key' => $currentKey,
                        'identifier' => $this->getIdentifier($currentKey),
                        'file' => $this->getFilePart($currentKey),
                        'value' => html_entity_decode($matches[1][$i]),
                        'overrides' => $this->getOverrides(
                            $sysFolderWithTranslationsUid,
                            $currentKey,
                            $matches[3][$i],
                            $matches[4][$i],
                            $matches[5][$i]
                        ),
                        'linkToBE' => (string) $this->getLinkToBE(
                            $sysFolderWithTranslationsUid,
                            $currentKey,
                            $matches[1][$i]
                        )
                    ];
                }
                $keys[] = $currentKey;
            }
        }
        return $labelTags;
    }

    /**
     * returns modified $content where all LLL:() markers inside of HTML tags are substituted with
     * their frontend representation
     *
     * @param string $content
     * @return string
     * @throws Exception
     */
    public function substituteLabelsInsideTags(string $content) : string
    {

        // find all labels inside of tag attributes (but not the ones outside of tags)
        // finds one or more labels inside of tag attribute values
        // seach for all tags with at least one LLL marker and add a data attribute
        // replaces the marker with translations and adds additional attribute 'data-translatelabel-attributes'
        // containing the label object encoded as json

        /*
        $search = '/(<[^>]*?)(LLL:\(' . self::regexQuote . '.*?' . self::regexQuote . ',' . self::regexQuote . '.*?' . self::regexQuote . ',' . self::regexQuote . '.*?' . self::regexQuote . ',' . self::regexQuote . '.*?' . self::regexQuote . ',' . self::regexQuote . '.*?' . self::regexQuote . '\))(.*?)(\/?>)/si';
        */

        $content = preg_replace_callback(
            self::getRegexForLLLMarker('.*?', '(<[^>]*?)(', ')(.*?)(\/?>)'),
            // iterate through each tag containing at least one LLL marker, f.e. '<img/>'
            function ($matches) {
                $attributesToTranslate = [];
                // iterate over each attribute, search for attributename="value"
                $search = '/([\w-]+)\s*=\s*"([^"]*)"/si';
                $attributeValueContent = preg_replace_callback(
                    $search,
                    function ($attributeMatches) use (&$attributesToTranslate) {
                        // iterate over each value and replace all its LLL: markers
                        $attributesToTranslate = [];
                        $labelCounter = -1;
                        $innerContent = preg_replace_callback(
                            self::getRegexForLLLMarker(),
                            function ($matches) use (&$attributesToTranslate, $attributeMatches, &$labelCounter) {
                                $attributesToTranslate[] = [
                                    'attribute' => trim($attributeMatches[1]),
                                    'key' => trim($matches[2]),
                                    'identifier' => $this->getIdentifier(trim($matches[2])),
                                    'translation' => $matches[1],
                                    'labelIndex' => ++$labelCounter
                                ];
                                return $this->renderLabelInsideTags(
                                    $matches[2], // key
                                    $matches[1] // translation string
                                );
                            },
                            $attributeMatches[2]
                        );
                        return $attributeMatches[1] . '="' . $innerContent .'"';
                    },
                    $matches[1] . $matches[2] . $matches[3]
                );
                return $attributeValueContent . ' data-translatelabels-role="translations-in-attributes" data-translatelabel-attributes="' . htmlentities(json_encode($attributesToTranslate)) . '" ' . $matches[4];
            },
            $content
        );
        if ($content === null) {
            throw new Exception(preg_last_error_msg(), preg_last_error());
        }
        return $content;
    }

    /**
     * substitutes LLL markers with markup to show a tooltip with tagname and link to admin panel
     * find <div>...LLL:...</div> but not <div data="...LLL:..."></div>
     *
     * @param $content
     * @param $sysFolderWithTranslationsUid
     * @param $message
     * @return string
     * @throws Exception
     * @throws RouteNotFoundException
     * @throws AspectNotFoundException
     */
    public function substituteLabels($content, $message) : string
    {

        /*
        $search = '/(LLL:\(' . $quote . '(.*?)' . $quote . ',' . $quote . '(.*?)' . $quote . ',' . $quote . '(.*?)' . $quote . ',' . $quote . '(.*?)' . $quote . ',' . $quote . '(.*?)' . $quote . '\))/i';
        */

        $content = preg_replace_callback(
            self::getRegexForLLLMarker('(.*?)', '(', ')'),
            function ($matches) use ($message) {
                return $this->renderLabelAsTooltip(
                    $matches[3], // key
                    $matches[2], // translation string
                    $message
                );
            },
            $content
        );
        if ($content === null) {
            throw new Exception(preg_last_error_msg(), preg_last_error());
        }
        return $content;
    }

    /**
     * returns regular expression for LLL markers with the syntax LLL:(param1, param2, param3, param4, param5)
     *
     * @param string $regexForParameterValue defaults to '(.*?)'
     * @param string $prefix
     * @param string $suffix
     * @param string $modifier
     * @return string
     */
    protected static function getRegexForLLLMarker(string $regexForParameterValue = self::REGEX_PARAMETERVALUE, string $prefix = '', string $suffix = '', string $modifier = 'si') : string
    {
        $paramExpression = self::REGEX_QUOTE . $regexForParameterValue . self::REGEX_QUOTE;
        $numberOfParameters = 5;
        $paramExpressionList = implode(',', array_fill(0, $numberOfParameters, $paramExpression));
        return '/' . $prefix . 'LLL:\(' . $paramExpressionList . '\)' . $suffix . '/' . $modifier;
    }

    /**
     * returns array of translation overrides for a label
     *
     * @param int $pid
     * @param string $labelKey
     * @param string $extensionName
     * @param string|null $optionalLanguageKey
     * @param string|null $optionalAlternativeLanguageKeys
     * @return array
     */
    protected function getOverrides(int $pid, string $labelKey, string $extensionName, string $optionalLanguageKey = null, string $optionalAlternativeLanguageKeys = null) : array
    {
        $reversedParts = explode(':', strrev($labelKey), 2);
        $languageFile = strrev($reversedParts[1]);
        $labelName = strrev($reversedParts[0]);
        $overrides[] = ['Xliff File', $languageFile];
        $locallangXMLOverride = $GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride'];
        $languageKey = $this->getCurrentLanguageKey();
        if (isset($locallangXMLOverride[$languageKey][$languageFile]) &&
            is_array($locallangXMLOverride[$languageKey][$languageFile])
        ) {
            foreach ($locallangXMLOverride[$languageKey][$languageFile] as $override) {
                $overrides[] = ['Xliff File', $override];
            }
        }
        $pluginName = (strpos($extensionName, 'tx_') === 0) ? $extensionName : 'tx_' . strtolower($extensionName);
        $typoScriptOverride = [];
        if (isset($GLOBALS['TSFE']->tmpl->setup['plugin.'][$pluginName . '.']['_LOCAL_LANG.'][$languageKey . '.']) &&
            is_array($GLOBALS['TSFE']->tmpl->setup['plugin.'][$pluginName . '.']['_LOCAL_LANG.'][$languageKey . '.'])) {
            $typoScriptOverride = ArrayUtility::flatten(
                $GLOBALS['TSFE']->tmpl->setup['plugin.'][$pluginName . '.']['_LOCAL_LANG.'][$languageKey . '.'],
                '.'
            );
        }
        if (!empty($typoScriptOverride['.' . $labelName])) {
            $overrides[] = ['TypoScript' , 'plugin.' . $pluginName . '._LOCAL_LANG.' . $languageKey . '.' . $labelName];
        }
        if (TranslationLabelUtility::getLabelFromDatabase($labelKey, $pid) !== null) {
            $overrides[] = ['Translation record', $labelKey];
        }
        return $overrides;
    }

    /**
     * @param string $labelKey
     * @return false|string
     */
    protected function getIdentifier(string $labelKey)
    {
        return substr(strrchr($labelKey, ':'), 1);
    }

    /**
     * @param string $labelKey
     * @return false|string
     */
    protected function getFilePart(string $labelKey)
    {
        if (strpos($labelKey, 'LLL:') === 0) {
            $labelKey = substr($labelKey, 4);
        }
        if (strpos($labelKey, 'EXT:') === 0) {
            $labelKey = substr($labelKey, 4);
        }
        $filePart = substr($labelKey, 0, strrpos($labelKey, ':'));
        return $filePart === '' ? $labelKey : $filePart;
    }

    /**
     * Fetches current site language from site configuration / language / typo3Language or
     * TypoScript config.language
     *
     * @return string key of current system language, f.e. 'default' or 'de'
     */
    protected function getCurrentLanguageKey()
    {
        $siteLanguage = null;
        if (isset($GLOBALS['TYPO3_REQUEST'])
            && $GLOBALS['TYPO3_REQUEST'] instanceof ServerRequestInterface
            && $GLOBALS['TYPO3_REQUEST']->getAttribute('language') instanceof SiteLanguage) {
            $siteLanguage = $GLOBALS['TYPO3_REQUEST']->getAttribute('language');
        }
        if ($siteLanguage) {
            $languageKey = $siteLanguage->getTypo3Language();
        } else {
            $languageKey = $this->config['config']['language'] ?? 'default';
        }
        return $languageKey;
    }

    /**
     * returns link to edit/create translation record in BE
     *
     * @param $sysFolderWithTranslationsUid
     * @param $key
     * @param $translationString
     * @return Uri
     * @throws RouteNotFoundException
     * @throws AspectNotFoundException
     */
    public function getLinkToBE($sysFolderWithTranslationsUid, $key, $translationString)
    {
        $context = GeneralUtility::makeInstance(Context::class);
        $sysLanguageUid = $context->getPropertyFromAspect('language', 'id');
        $uri = $this->getLinkToEditTranslation(
            $sysFolderWithTranslationsUid,
            $translationString,
            $key,
            $sysLanguageUid
        );
        return $uri;
    }

    /**
     * Generates link to edit translation records in backend
     * Works in conjunction with Sitegeist\Translatelabels\Middleware\CreateLabelResolver which
     * does some magic to automatically create missing translations records in default language and prepares
     * localizations
     *
     * @param $sysFolderWithTranslationsUid
     * @param $translation
     * @param $key
     * @param $sysLanguageUid
     * @return Uri
     * @throws RouteNotFoundException
     */
    protected function getLinkToEditTranslation($sysFolderWithTranslationsUid, $translation, $key, $sysLanguageUid)
    {
        $uri = $this->getLinkToBEModule('record_edit', [
            'id' => $sysFolderWithTranslationsUid,
            'tx_translatelabels[key]' => $key,
            'tx_translatelabels[language]' => $sysLanguageUid,
            'tx_translatelabels[pid]' => $sysFolderWithTranslationsUid,
            'tx_translatelabels[translation]' => $translation
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
    protected function getLinkToBEModule($route, $urlParameters) : Uri
    {
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        try {
            $uri = $uriBuilder->buildUriFromRoute($route, $urlParameters);
        } catch (RouteNotFoundException $e) {
            $uri = $uriBuilder->buildUriFromRoutePath($route, $urlParameters);
        }
        return $uri;
    }

    /**
     * renders frontend representation for a LLL:() marker inside a HTML tag
     *
     * @param $key
     * @param $translationString
     * @return string
     */
    protected function renderLabelInsideTags($key, $translationString) : string
    {
        return $translationString . ' (LABEL: ' . htmlentities($this->getIdentifier($key)) .')';
    }

    /**
     * @param $key
     * @param $translationString
     * @param string $message
     * @param string $target
     * @return string
     */
    protected function renderLabelAsTooltip($key, $translationString, string $message = 'Translate label \'%s\'...', $target = 'backend')
    {
        return '<span class="translatelabels-tooltip">'
            . '<span class="translatelabels-translation" data-translatelabels-role="translation" data-translatelabels-key="' . $key .'">' . $translationString . '</span>'
            . '<span style="display: none;" class="translatelabels-tooltip-inner"><span data-translatelabels-link="' . $key . '">' . vsprintf($message, [ $this->getIdentifier($key) ]) . '</span></span>'
            . '</span>';
    }
}
