<?php
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
defined('TYPO3') || die('Access denied.');

call_user_func(
    function () {

        ExtensionManagementUtility::addStaticFile('translatelabels', 'Configuration/TypoScript', 'translate_labels');

        ExtensionManagementUtility::addLLrefForTCAdescr('tx_translatelabels_domain_model_translation', 'EXT:translatelabels/Resources/Private/Language/locallang_csh_tx_translatelabels_domain_model_translation.xlf');
        ExtensionManagementUtility::allowTableOnStandardPages('tx_translatelabels_domain_model_translation');
    }
);
