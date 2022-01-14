<?php

/**
 * This file is part of the package netresearch/nr-image-sitemap.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\NrImageSitemap\Seo;

use Netresearch\NrImageSitemap\Domain\Model\ImageFileReference;
use Netresearch\NrImageSitemap\Domain\Repository\ImageFileReferenceRepository;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Resource\AbstractFile;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Object\Exception;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Seo\XmlSitemap\AbstractXmlSitemapDataProvider;

use function count;
use function in_array;

/**
 * Generate sitemap for images.
 *
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @license Netresearch https://www.netresearch.de
 * @link    https://www.netresearch.de
 */
class ImagesXmlSitemapDataProvider extends AbstractXmlSitemapDataProvider
{
    private const DEFAULT_TABLES = [
        'tt_content',
        'pages',
    ];

    /**
     * @var ImageFileReferenceRepository
     */
    private ImageFileReferenceRepository $imageFileReferenceRepository;

    /**
     * @var UriBuilder
     */
    private UriBuilder $uriBuilder;

    /**
     * Constructor.
     *
     * @param ServerRequestInterface     $request
     * @param string                     $key
     * @param array                      $config
     * @param ContentObjectRenderer|null $cObj
     */
    public function __construct(
        ServerRequestInterface $request,
        string $key,
        array $config = [],
        ContentObjectRenderer $cObj = null
    ) {
        parent::__construct($request, $key, $config, $cObj);

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        $this->imageFileReferenceRepository
            = $objectManager->get(ImageFileReferenceRepository::class);
        $this->uriBuilder
            = $objectManager->get(UriBuilder::class);

        $this->generateItems();
    }

    /**
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws Exception
     * @throws InvalidQueryException
     */
    public function generateItems(): void
    {
        /** @var ImageFileReference[] $images */
        $images = $this->imageFileReferenceRepository->findAllByType(
            [
                AbstractFile::FILETYPE_IMAGE,
            ]
        );

        $items = [];

        if ($images && count($images)) {
            foreach ($images as $image) {
                // Ignore all table other than default ones
                if (!in_array($image->getTablenames(), self::DEFAULT_TABLES)) {
                    continue;
                }

                $frontendUri = $this->uriBuilder
                    ->reset()
                    ->setCreateAbsoluteUri(true)
                    ->setTargetPageUid($image->getPid())
                    ->buildFrontendUri();

                // Create hash to merge all images belonging to same site
                $hashedUri = md5($frontendUri);

                $items[$hashedUri]['uri'] = $frontendUri;
                $items[$hashedUri]['images'][] = $image;
            }
        }

        $this->items = $items;
    }
}
