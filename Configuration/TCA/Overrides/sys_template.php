<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
/**
 * TCA override for sys_template table
 */

defined('TYPO3') or die();

call_user_func(static function () {
    ExtensionManagementUtility::addStaticFile(
        'nr_image_sitemap',
        'Configuration/TypoScript',
        'Netresearch: Image Sitemap'
    );
});
