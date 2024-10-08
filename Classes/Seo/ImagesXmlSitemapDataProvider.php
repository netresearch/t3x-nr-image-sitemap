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
use TYPO3\CMS\Seo\XmlSitemap\Exception\MissingConfigurationException;
use function count;

/**
 * Generate sitemap for images.
 *
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @license Netresearch https://www.netresearch.de
 * @link    https://www.netresearch.de
 */
class ImagesXmlSitemapDataProvider extends AbstractXmlSitemapDataProvider
{
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
     *
     * @throws Exception
     * @throws InvalidQueryException
     * @throws \Doctrine\DBAL\Driver\Exception
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
     * @throws InvalidQueryException
     * @throws Exception
     * @throws MissingConfigurationException
     */
    public function generateItems(): void
    {
        $tables = GeneralUtility::trimExplode(',', $this->config['tables']);

        if (empty($tables)) {
            throw new MissingConfigurationException(
                'No configuration found for sitemap ' . $this->getKey(),
                1652249698
            );
        }

        $excludedDoktypes = [];
        if (!empty($this->config['excludedDoktypes'])) {
            $excludedDoktypes = GeneralUtility::intExplode(',', $this->config['excludedDoktypes']);
        }

        $additionalWhere = '';
        if (!empty($this->config['additionalWhere'])) {
            $additionalWhere = $this->config['additionalWhere'];
        }

        if (!empty($this->config['rootPage'])) {
            $rootPageId = (int) $this->config['rootPage'];
        } else {
            $rootPageId = $this->request->getAttribute('site')->getRootPageId();
        }

        $treeList      = $this->cObj->getTreeList(-$rootPageId, 99);
        $treeListArray = GeneralUtility::intExplode(',', $treeList);

        /** @var ImageFileReference[] $images */
        $images = $this->imageFileReferenceRepository->findAllImages(
            [
                AbstractFile::FILETYPE_IMAGE,
            ],
            $treeListArray,
            $tables,
            $excludedDoktypes,
            $additionalWhere
        );

        $items = [];

        if ($images && count($images)) {
            foreach ($images as $image) {
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
