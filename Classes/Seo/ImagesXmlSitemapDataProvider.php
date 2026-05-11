<?php

/*
 * This file is part of the package netresearch/nr-image-sitemap.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\NrImageSitemap\Seo;

use Doctrine\DBAL\Driver\Exception;
use Netresearch\NrImageSitemap\Domain\Repository\ImageFileReferenceRepository;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Resource\FileType;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Typolink\LinkFactory;
use TYPO3\CMS\Seo\XmlSitemap\AbstractXmlSitemapDataProvider;
use TYPO3\CMS\Seo\XmlSitemap\Exception\MissingConfigurationException;

/**
 * Generate sitemap for images.
 *
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @license Netresearch https://www.netresearch.de
 *
 * @see    https://www.netresearch.de
 */
final class ImagesXmlSitemapDataProvider extends AbstractXmlSitemapDataProvider
{
    private readonly ImageFileReferenceRepository $imageFileReferenceRepository;

    private readonly PageRepository $pageRepository;

    private readonly SiteFinder $siteFinder;

    private readonly LinkFactory $linkFactory;

    /**
     * Constructor signature is fixed by the {@see XmlSitemapDataProviderInterface}
     * contract, which is part of typo3/cms-seo and cannot be altered here.
     * The four ergebnis rule violations for $config / $cObj are suppressed in
     * Build/phpstan.neon for this file.
     *
     * @param array<string, mixed> $config
     *
     * @throws InvalidQueryException
     * @throws MissingConfigurationException
     * @throws Exception
     */
    public function __construct(
        ServerRequestInterface $request,
        string $key,
        array $config = [],
        ?ContentObjectRenderer $cObj = null,
    ) {
        parent::__construct($request, $key, $config, $cObj);

        $this->imageFileReferenceRepository = GeneralUtility::makeInstance(ImageFileReferenceRepository::class);
        $this->pageRepository               = GeneralUtility::makeInstance(PageRepository::class);
        $this->siteFinder                   = GeneralUtility::makeInstance(SiteFinder::class);
        $this->linkFactory                  = GeneralUtility::makeInstance(LinkFactory::class);

        $this->generateItems();
    }

    /**
     * @throws InvalidQueryException
     * @throws MissingConfigurationException
     * @throws Exception
     */
    public function generateItems(): void
    {
        $tables = GeneralUtility::trimExplode(',', (string) ($this->config['tables'] ?? ''));

        if ($tables === []) {
            throw new MissingConfigurationException(
                'No configuration found for sitemap ' . $this->getKey(),
                1_652_249_698,
            );
        }

        $excludedDoktypesConfig = (string) ($this->config['excludedDoktypes'] ?? '');
        $excludedDoktypes       = $excludedDoktypesConfig !== ''
            ? GeneralUtility::intExplode(',', $excludedDoktypesConfig)
            : [];

        $additionalWhere = (string) ($this->config['additionalWhere'] ?? '');

        $rootPageConfig = (string) ($this->config['rootPage'] ?? '');
        $rootPageId     = $rootPageConfig !== ''
            ? (int) $rootPageConfig
            : $this->request->getAttribute('site')->getRootPageId();

        $treeListArray = $this->pageRepository->getPageIdsRecursive([$rootPageId], 99);

        $images = $this->imageFileReferenceRepository->findAllImages(
            [
                FileType::IMAGE,
            ],
            $treeListArray,
            $tables,
            $excludedDoktypes,
            $additionalWhere,
        );

        if ($images === []) {
            return;
        }

        $items = [];

        foreach ($images as $image) {
            $link    = $this->linkFactory->createUri((string) $image->getPid());
            $site    = $this->siteFinder->getSiteByPageId($image->getPid());
            $baseUrl = $site->getBase()->__toString();

            // Construct full URL
            $frontendUri = rtrim($baseUrl, '/') . '/' . ltrim($link->getUrl(), '/');

            // Create hash to merge all images belonging to same site
            $hashedUri = md5($frontendUri);

            $items[$hashedUri]['uri']      = $frontendUri;
            $items[$hashedUri]['images'][] = $image;
        }

        $this->items = $items;
    }
}
