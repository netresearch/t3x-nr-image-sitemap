<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
/**
 * TCA override for sys_template table
 */

defined('TYPO3') || die();

call_user_func(static function (): void {
    ExtensionManagementUtility::addStaticFile(
        'nr_image_sitemap',
        'Configuration/TypoScript',
        'Netresearch: Image Sitemap'
    );
});
