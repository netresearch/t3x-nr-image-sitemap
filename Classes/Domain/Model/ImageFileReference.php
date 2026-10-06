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

namespace Netresearch\NrImageSitemap\Domain\Model;

use TYPO3\CMS\Extbase\Domain\Model\FileReference;

/**
 * The image file reference domain model.
 *
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @license AGPL-3.0-or-later https://www.gnu.org/licenses/agpl-3.0.html
 *
 * @see    https://www.netresearch.de
 */
final class ImageFileReference extends FileReference
{
    protected string $title = '';

    protected string $description = '';

    protected string $tablenames = '';

    /**
     * Absolute URL of the frontend site root, for example `https://example.org/sub/`;
     * not persisted, set by the sitemap data provider from the current request.
     */
    protected string $siteUrl = '';

    /**
     * Scheme and host of the current request, for example `https://example.org`;
     * not persisted, set by the sitemap data provider.
     */
    protected string $requestHost = '';

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
     * no access to that request. The data provider
     * {@see \Netresearch\NrImageSitemap\Seo\ImagesXmlSitemapDataProvider} sets the site URL
     * of the request through {@see self::setSiteUrl()}, and the template renders
     * {@see self::getLocation()}.
     */
    public function getPublicUrl(): string
    {
        return ltrim((string) $this->getOriginalResource()->getPublicUrl(), '/');
    }

    public function getTablenames(): string
    {
        return $this->tablenames;
    }

    public function setBaseUrls(string $siteUrl, string $requestHost): void
    {
        $this->siteUrl     = $siteUrl;
        $this->requestHost = $requestHost;
    }

    /**
     * Returns the absolute URL of the referenced file for the sitemap.
     *
     * A public URL that already names a host (a CDN or another storage) is used as is.
     * A URL with a leading slash is relative to the host and gets the scheme and host of
     * the request; TYPO3 adds the site path itself when the site lives in a
     * subdirectory. Any other URL is relative to the site root and gets the site URL.
     */
    public function getLocation(): string
    {
        $publicUrl = (string) $this->getOriginalResource()->getPublicUrl();

        if ($publicUrl === '' || preg_match('#^([a-z][a-z0-9+.-]*:)?//#i', $publicUrl) === 1) {
            return $publicUrl;
        }

        if (str_starts_with($publicUrl, '/')) {
            return rtrim($this->requestHost, '/') . $publicUrl;
        }

        return rtrim($this->siteUrl, '/') . '/' . $publicUrl;
    }
}
