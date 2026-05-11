<?php

/*
 * This file is part of the package netresearch/nr-image-sitemap.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\NrImageSitemap\Domain\Model;

use TYPO3\CMS\Core\Utility\GeneralUtility;
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

    public function getPublicUrl(): string
    {
        return GeneralUtility::getIndpEnv('TYPO3_SITE_URL')
            . $this->getOriginalResource()->getPublicUrl();
    }

    public function getTablenames(): string
    {
        return $this->tablenames;
    }
}
