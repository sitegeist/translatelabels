<?php

namespace Sitegeist\Translatelabels\Hooks;


use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Sitegeist\Translatelabels\Renderer\FrontendRenderer;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use Sitegeist\Translatelabels\Exception\LabelReplaceException;
use Sitegeist\Translatelabels\Utility\TranslationLabelUtility;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;


class UpdateLabelsHook
{
    public function processDatamap_afterDatabaseOperations($status, $table, $id, array $fieldArray, DataHandler $dataHandler)
    {
        if ($table === 'tx_translatelabels_domain_model_translation') {
            $cache = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\CacheManager::class)->getCache('translatelabels_cache');
            $cache->flush();
        }
    }
}
