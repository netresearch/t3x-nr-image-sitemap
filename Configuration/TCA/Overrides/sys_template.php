<?php

/**
 * TCA override for sys_template table
 */

defined('TYPO3_MODE') or die();

call_user_func(static function () {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
        'nr_image_sitemap',
        'Configuration/TypoScript',
        'Netresearch: Image Sitemap'
    );
});
