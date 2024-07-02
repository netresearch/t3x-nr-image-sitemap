<?php

/**
 * This file is part of the package netresearch/nr-image-sitemap.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

return [
    'nr_image_sitemap_extension_icon' => [
        'provider' => SvgIconProvider::class,
        'source'   => 'EXT:nr_image_sitemap/Resources/Public/Icons/Extension.svg',
    ],
];
