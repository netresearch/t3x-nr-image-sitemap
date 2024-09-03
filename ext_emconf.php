<?php

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
    'version'     => '12.0.0',
    'category'    => 'plugin',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-',
            'seo'   => '12.4.0-',
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
