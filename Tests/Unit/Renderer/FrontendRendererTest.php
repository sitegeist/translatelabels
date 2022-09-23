<?php

namespace Sitegeist\Translatelabels\Tests\Unit\Renderer;

/**
 *
 * This file is part of the "translatelabels" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 *
 */

use TYPO3\TestingFramework\Core\BaseTestCase;
use Sitegeist\Translatelabels\Renderer\FrontendRenderer;

/**
 * Test class for FrontendRendererDataProvider
 *
 */
class FrontendRendererTest extends BaseTestCase
{
    /**
     * Data Provider
     *
     * @return array
     */
    public function substituteLabelsInsideTagsSubstitutesLabelsDataProvider()
    {
        return [
            'marker outside tags' => [
                '<div style="background-color: yellow">Label außerhalb eines Tags: LLL:(&#039;Name2121&#039;,&#039;dummy/Resources/Private/Language/locallang.xlf:tx_dummy_domain_model_konfiguration.name&#039;,&#039;Dummy&#039;,&#039;&#039;,&#039;&#039;)</div>',
                '<div style="background-color: yellow">Label außerhalb eines Tags: LLL:(&#039;Name2121&#039;,&#039;dummy/Resources/Private/Language/locallang.xlf:tx_dummy_domain_model_konfiguration.name&#039;,&#039;Dummy&#039;,&#039;&#039;,&#039;&#039;)</div>',
            ],
            'one marker inside tags' => [
                '<div style="background-color: skyblue" data-role="LLL:(&#039;Name123&#039;,&#039;dummy/Resources/Private/Language/locallang.xlf:tx_dummy_domain_model_konfiguration.name1&#039;,&#039;Dummy&#039;,&#039;&#039;,&#039;&#039;)">Hier steht eine Übersetzung in einem Attribut des div-Elements.</div>',
                '<div style="background-color: skyblue" data-role="Name123 (LABEL: tx_dummy_domain_model_konfiguration.name1)" data-translatelabels-role="translations-in-attributes" data-translatelabel-attributes="[{&quot;attribute&quot;:&quot;data-role&quot;,&quot;key&quot;:&quot;dummy\/Resources\/Private\/Language\/locallang.xlf:tx_dummy_domain_model_konfiguration.name1&quot;,&quot;identifier&quot;:&quot;tx_dummy_domain_model_konfiguration.name1&quot;,&quot;translation&quot;:&quot;Name123&quot;,&quot;labelIndex&quot;:0}]" >Hier steht eine Übersetzung in einem Attribut des div-Elements.</div>'
            ]
        ];
    }

    /**
     * @test
     * @dataProvider substituteLabelsInsideTagsSubstitutesLabelsDataProvider
     * @return void
     */
    public function substituteLabelsInsideTagsSubstitutesLabels($content, $expectedResult)
    {
        $frontendRenderer = new FrontendRenderer();
        $this->assertEquals($expectedResult, $frontendRenderer->substituteLabelsInsideTags($content));
    }

    /**
     * Data Provider
     *
     * @return array
     */
    public function substituteLabelsSubstitutesLabelsDataProvider()
    {
        return [
            'marker outside tags' => [
                '<div style="background-color: yellow">Label außerhalb eines Tags: LLL:(&#039;Name2121&#039;,&#039;dummy/Resources/Private/Language/locallang.xlf:tx_dummy_domain_model_konfiguration.name&#039;,&#039;Dummy&#039;,&#039;&#039;,&#039;&#039;)</div>',
                '<div style="background-color: yellow">Label außerhalb eines Tags: <span class="translatelabels-tooltip"><span class="translatelabels-translation" data-translatelabels-role="translation" data-translatelabels-key="dummy/Resources/Private/Language/locallang.xlf:tx_dummy_domain_model_konfiguration.name">Name2121</span><span style="display: none;" class="translatelabels-tooltip-inner"><span data-translatelabels-link="dummy/Resources/Private/Language/locallang.xlf:tx_dummy_domain_model_konfiguration.name">Translate label &quot;tx_dummy_domain_model_konfiguration.name&quot; ...</span></span></span></div>'
            ],
            'markers without fluid quoting' => [
                '<div>Label außerhalb eines Tags: LLL:(\'Name2121\',\'dummy/Resources/Private/Language/locallang.xlf:tx_dummy_domain_model_konfiguration.name\',\'Dummy\',\'\',\'\')</div>',
                '<div>Label außerhalb eines Tags: <span class="translatelabels-tooltip"><span class="translatelabels-translation" data-translatelabels-role="translation" data-translatelabels-key="dummy/Resources/Private/Language/locallang.xlf:tx_dummy_domain_model_konfiguration.name">Name2121</span><span style="display: none;" class="translatelabels-tooltip-inner"><span data-translatelabels-link="dummy/Resources/Private/Language/locallang.xlf:tx_dummy_domain_model_konfiguration.name">Translate label &quot;tx_dummy_domain_model_konfiguration.name&quot; ...</span></span></span></div>'
            ],
            'multiline labels outside tags' => [
                '<p class="consent_infotext">LLL:(\'line 1<br>
line 2<br>
<br>
line 3\',\'sitepackage/Resources/Private/Language/Frontend/locallang.xlf:professionalConsent.text\',\'\',\'\',\'\')</p>',
                '<p class="consent_infotext"><span class="translatelabels-tooltip"><span class="translatelabels-translation" data-translatelabels-role="translation" data-translatelabels-key="sitepackage/Resources/Private/Language/Frontend/locallang.xlf:professionalConsent.text">line 1<br>
line 2<br>
<br>
line 3</span><span style="display: none;" class="translatelabels-tooltip-inner"><span data-translatelabels-link="sitepackage/Resources/Private/Language/Frontend/locallang.xlf:professionalConsent.text">Translate label &quot;professionalConsent.text&quot; ...</span></span></span></p>'
            ]
        ];
    }

    /**
     * @test
     * @dataProvider substituteLabelsSubstitutesLabelsDataProvider
     * @return void
     */
    public function substituteLabelsSubstitutesLabels($content, $expectedResult)
    {
        $frontendRenderer = new FrontendRenderer();
        $this->assertEquals($expectedResult, $frontendRenderer->substituteLabels($content, 'Translate label &quot;%s&quot; ...'));
    }
}
