<?php
namespace Sitegeist\Translatelabels\Domain\Repository;

/**
 *
 * This file is part of the "translatelabels" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 */

/**
 * The repository for Translations
 */
class TranslationRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{
    public function initializeObject()
    {
        /**
         * @var \TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings $querySettings
         */
        $querySettings = $this->objectManager->get(\TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings::class);
        $querySettings->setRespectStoragePage(false);
        $this->setDefaultQuerySettings($querySettings);
    }

    /**
     * Finds a translation record with given $label in language in specific pid
     *
     * @param $label
     * @param $languageUid
     * @param $pid
     * @return object
     */
    public function findOneByLabelKeyInLanguageInPid($label, $languageUid, $pid)
    {
        $query = $this->createQuery();

        //Disable Language restriction
        $query->getQuerySettings()
            ->setRespectSysLanguage(false)
            // default is 'hideNonTranslated' which results in returning NULL if record exists in default language
            // but not in default language
            ->setLanguageOverlayMode(false)
            ->setRespectStoragePage(false);

        $constraints = $query->logicalAnd(
            [
                $query->equals('labelkey', $label),
                $query->equals('sys_language_uid', $languageUid),
                $query->equals('pid', $pid)
            ]
        );

        $result = $query->matching($constraints)->execute();
        return $result->getFirst();
    }

    /**
     * @param string $label
     * @param int $pid
     * @return object
     */
    public function findOneByLabelKeyInPid(string $label, int $pid)
    {
        $query = $this->createQuery();

        $query->getQuerySettings()
            ->setRespectStoragePage(false);

        $constraints = $query->logicalAnd(
            [
                $query->equals('labelkey', $label),
                $query->equals('pid', $pid)
            ]
        );

        $result = $query->matching($constraints)->execute();
        return $result->getFirst();
    }
}
