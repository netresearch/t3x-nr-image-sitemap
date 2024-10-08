<?php

/**
 * This file is part of the package netresearch/nr-image-sitemap.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

// Defines the mapping of the table record to a domain model class
return [
    \Netresearch\NrImageSitemap\Domain\Model\ImageFileReference::class => [
        'tableName' => 'sys_file_reference',
    ],
];
