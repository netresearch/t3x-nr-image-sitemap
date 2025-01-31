<?php

/**
 * This file is part of the package netresearch/nr-image-sitemap.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * TCA override for sys_template table.
 */
defined('TYPO3') || exit;

call_user_func(static function (): void {
    ExtensionManagementUtility::addStaticFile(
        'nr_image_sitemap',
        'Configuration/TypoScript',
        'Netresearch: Image Sitemap'
    );
});
