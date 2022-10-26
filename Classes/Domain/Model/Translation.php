<?php
namespace Sitegeist\Translatelabels\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 *
 * This file is part of the "translatelabels" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 *
 */
/**
 * Translation
 */
class Translation extends AbstractEntity
{
    /**
     * labelkey
     *
     * @var string
     */
    protected $labelkey = '';

    /**
     * translation
     *
     * @var string
     */
    protected $translation = '';

    /**
     * language
     *
     * @var int
     */
    protected $language = 0;

    /**
     * @var int
     */
    protected $l10n_parent = 0;

    /**
     * Returns the labelkey
     *
     * @return string $labelkey
     */
    public function getLabelkey()
    {
        return $this->labelkey;
    }

    /**
     * Sets the labelkey
     *
     * @param string $labelkey
     * @return void
     */
    public function setLabelkey($labelkey)
    {
        $this->labelkey = $labelkey;
    }

    /**
     * Returns the translation
     *
     * @return string $translation
     */
    public function getTranslation()
    {
        return $this->translation;
    }

    /**
     * Sets the translation
     *
     * @param string $translation
     * @return void
     */
    public function setTranslation($translation)
    {
        $this->translation = $translation;
    }

    /**
     * Returns the language
     *
     * @return int $language
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Sets the language
     *
     * @param int $language
     * @return void
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }

    /**
     * @return int
     */
    public function getLocalizedUid()
    {
        return $this->_localizedUid;
    }

    public function setLocalizedUid($l10nParent)
    {
        $this->_localizedUid = $l10nParent;
    }

    /**
     * @return int
     */
    public function getLanguageUid()
    {
        return $this->_languageUid;
    }

    public function setLanguageUid($sysLanguageUid)
    {
        $this->_languageUid = $sysLanguageUid;
    }

    public function setL10nParent($l10nParent)
    {
        $this->l10n_parent = $l10nParent;
    }
}
