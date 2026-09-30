<?php

/*
 * This file is part of the package netresearch/nr-image-sitemap.
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') || exit;

call_user_func(static function (): void {
    ExtensionManagementUtility::addStaticFile(
        'nr_image_sitemap',
        'Configuration/TypoScript',
        'Netresearch: Image Sitemap',
    );
});
