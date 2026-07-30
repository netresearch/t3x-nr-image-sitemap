<?php

/*
 * This file is part of the package netresearch/nr-image-sitemap.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\NrImageSitemap\Domain\Model;

use TYPO3\CMS\Extbase\Domain\Model\FileReference;

/**
 * The image file reference domain model.
 *
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @license Netresearch https://www.netresearch.de
 *
 * @see    https://www.netresearch.de
 */
final class ImageFileReference extends FileReference
{
    protected string $title = '';

    protected string $description = '';

    protected string $tablenames = '';

    public function getTitle(): string
    {
        if ($this->title !== '' && $this->title !== '0') {
            return $this->title;
        }

        if ($this->getOriginalResource()->hasProperty('title')) {
            return (string) $this->getOriginalResource()->getProperty('title');
        }

        return '';
    }

    public function getDescription(): string
    {
        if ($this->description !== '' && $this->description !== '0') {
            return $this->description;
        }

        if ($this->getOriginalResource()->hasProperty('description')) {
            return (string) $this->getOriginalResource()->getProperty('description');
        }

        return '';
    }

    /**
     * Returns the public URL of the referenced file, relative to the site root and
     * without a leading slash (for example `fileadmin/image.jpg`).
     *
     * Prefixing the site URL is deliberately not done here: it used to be read from
     * GeneralUtility::getIndpEnv('TYPO3_SITE_URL'), which is deprecated since TYPO3 v14.3
     * in favour of NormalizedParams taken from the PSR-7 request, and a domain model has
     * no access to that request. The composition now happens in
     * {@see \Netresearch\NrImageSitemap\Seo\ImagesXmlSitemapDataProvider}, which passes
     * the site URL to the template as `item.baseUrl`.
     */
    public function getPublicUrl(): string
    {
        return ltrim((string) $this->getOriginalResource()->getPublicUrl(), '/');
    }

    public function getTablenames(): string
    {
        return $this->tablenames;
    }
}
