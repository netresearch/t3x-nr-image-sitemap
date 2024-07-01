<?php

/**
 * This file is part of the package netresearch/nr-image-sitemap.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

/***************************************************************
 * Extension Manager/Repository config file for ext "nr_image_sitemap".
 *
 * Auto generated 14-12-2020 09:00
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = [
    'title'       => 'Netresearch - Sitemap Extension',
    'description' => 'Provides a data provider to use with the typo3/cms-seo extension, to create an image sitemap',
    'version'     => '1.0.0',
    'category'    => 'plugin',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.0-',
            'seo'   => '11.5.0-',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],
    'autoload' => [
        'psr-4' => [
            'Netresearch\\NrImageSitemap\\' => 'Classes',
        ],
    ],
    'state'          => 'stable',
    'author'         => 'Rico Sonntag',
    'author_email'   => 'rico.sonntag@netresearch.de',
    'author_company' => 'Netresearch DTT GmbH',
];
