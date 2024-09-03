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
    'title'            => 'Netresearch - Sitemap Extension',
    'description'      => 'Provides a data provider to use with the typo3/cms-seo extension, to create an image sitemap',
    'version'          => '10.1.4',
    'category'         => 'plugin',
    'constraints'      => [
        'depends'   => [
            'typo3' => '10.4.0-10.4.99',
            'seo'   => '10.4.0-10.4.99',
        ],
        'conflicts' => [
        ],
        'suggests'  => [
        ],
    ],
    'autoload'         => [
        'psr-4' => [
            'Netresearch\\NrImageSitemap\\' => 'Classes',
        ],
    ],
    'state'            => 'stable',
    'uploadfolder'     => false,
    'createDirs'       => '',
    'clearCacheOnLoad' => true,
    'author'           => 'Rico Sonntag',
    'author_email'     => 'rico.sonntag@netresearch.de',
    'author_company'   => 'Netresearch DTT GmbH',
];
