<?php

/**
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
 * @link    https://www.netresearch.de
 */
class ImageFileReference extends FileReference
{
    /**
     * @var string
     */
    protected string $title = '';

    /**
     * @var string
     */
    protected string $description = '';

    /**
     * @var string
     */
    protected string $tablenames = '';

    /**
     * Returns the title.
     *
     * @return string|null
     */
    public function getTitle(): ?string
    {
        if ($this->title !== '' && $this->title !== '0') {
            return $this->title;
        }

        if ($this->getOriginalResource()->hasProperty('title')) {
            return $this->getOriginalResource()->getProperty('title');
        }

        return null;
    }

    /**
     * Returns the description.
     *
     * @return string|null
     */
    public function getDescription(): ?string
    {
        if ($this->description !== '' && $this->description !== '0') {
            return $this->description;
        }

        if ($this->getOriginalResource()->hasProperty('description')) {
            return $this->getOriginalResource()->getProperty('description');
        }

        return null;
    }

    /**
     * Returns the URL of the file.
     *
     * @return string
     */
    public function getPublicUrl(): string
    {
        return GeneralUtility::getIndpEnv('TYPO3_SITE_URL')
            . $this->getOriginalResource()->getPublicUrl();
    }

    /**
     * Returns the name of the table the file belongs too.
     *
     * @return string
     */
    public function getTablenames(): string
    {
        return $this->tablenames;
    }
}
