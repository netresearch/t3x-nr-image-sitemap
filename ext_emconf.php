<?php

/**
 * This file is part of the package netresearch/nr-image-sitemap.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */


$EM_CONF[$_EXTKEY] = [
    'title'       => 'Netresearch - Sitemap Extension',
    'description' => 'Provides a data provider to use with the typo3/cms-seo extension, to create an image sitemap',
    'version'     => '13.0.6',
    'category'    => 'plugin',
    'constraints' => [
        'depends' => [
            'typo3' => '13.0.0-13.4.99',
            'seo'   => '13.0.0-13.4.99',
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
