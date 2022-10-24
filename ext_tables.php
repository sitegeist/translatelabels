<?php
defined('TYPO3') || die('Access denied.');

call_user_func(
    function () {

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('translatelabels', 'Configuration/TypoScript', 'translate_labels');

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_translatelabels_domain_model_translation', 'EXT:translatelabels/Resources/Private/Language/locallang_csh_tx_translatelabels_domain_model_translation.xlf');
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_translatelabels_domain_model_translation');
    }
);
