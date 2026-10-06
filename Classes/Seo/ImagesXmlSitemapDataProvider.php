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

namespace Netresearch\NrImageSitemap\Seo;

use Doctrine\DBAL\Driver\Exception;
use Netresearch\NrImageSitemap\Domain\Repository\ImageFileReferenceRepository;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Domain\Access\RecordAccessVoter;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Exception\Page\PageNotFoundException;
use TYPO3\CMS\Core\Exception\Page\RootLineException;
use TYPO3\CMS\Core\Resource\FileType;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Typolink\LinkFactory;
use TYPO3\CMS\Seo\XmlSitemap\AbstractXmlSitemapDataProvider;
use TYPO3\CMS\Seo\XmlSitemap\Exception\MissingConfigurationException;

/**
 * Generate sitemap for images.
 *
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @license AGPL-3.0-or-later https://www.gnu.org/licenses/agpl-3.0.html
 *
 * @see    https://www.netresearch.de
 */
final class ImagesXmlSitemapDataProvider extends AbstractXmlSitemapDataProvider
{
    /**
     * Page type "Backend User Section": TYPO3 serves such a page and the pages below it
     * only to a request with a backend login.
     */
    private const DOKTYPE_BACKEND_USER_SECTION = 6;

    private readonly ImageFileReferenceRepository $imageFileReferenceRepository;

    private readonly PageRepository $pageRepository;

    private readonly SiteFinder $siteFinder;

    private readonly LinkFactory $linkFactory;

    /**
     * Constructor signature is fixed by the {@see \TYPO3\CMS\Seo\XmlSitemap\XmlSitemapDataProviderInterface}
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
        $tables = GeneralUtility::trimExplode(',', (string) ($this->config['tables'] ?? ''), true);

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

        $treeListArray = $this->filterByRootLineAccess(
            $this->pageRepository->getPageIdsRecursive([$rootPageId], 99),
        );

        $images = $this->imageFileReferenceRepository->findAllImages(
            [
                // The case value, not the enum instance: it is bound as an integer array
                // parameter and DBAL cannot convert an enum instance to int.
                FileType::IMAGE->value,
            ],
            $treeListArray,
            $tables,
            $excludedDoktypes,
            $additionalWhere,
        );

        if ($images === []) {
            return;
        }

        // Absolute URL of the frontend site root, used to turn the site-relative public URL
        // of a file reference into an absolute one. This is the documented replacement for
        // GeneralUtility::getIndpEnv('TYPO3_SITE_URL'), which the domain model used before
        // and which is deprecated since TYPO3 v14.3.
        $normalizedParams = $this->request->getAttribute('normalizedParams');
        $siteUrl          = $normalizedParams?->getSiteUrl() ?? '';
        $requestHost      = $normalizedParams?->getRequestHost() ?? '';

        $items = [];

        foreach ($images as $image) {
            $link    = $this->linkFactory->createUri((string) $image->getPid());
            $site    = $this->siteFinder->getSiteByPageId($image->getPid());
            $baseUrl = $site->getBase()->__toString();

            // Construct full URL
            $frontendUri = rtrim($baseUrl, '/') . '/' . ltrim($link->getUrl(), '/');

            // Create hash to merge all images belonging to same site
            $hashedUri = md5($frontendUri);

            $image->setBaseUrls($siteUrl, $requestHost);

            $items[$hashedUri]['uri']      = $frontendUri;
            $items[$hashedUri]['baseUrl']  = $siteUrl;
            $items[$hashedUri]['images'][] = $image;
        }

        $this->items = $items;
    }

    /**
     * Keeps the pages a frontend request of the current user would be served.
     *
     * getPageIdsRecursive() checks the pages below the start page from the top down, but
     * neither the start page itself nor the pages above it, and it descends into the
     * target of a mount point without checking the target's own root line. Each page is
     * therefore checked against its own root line, as TYPO3 does before it serves a page:
     * the page itself must be visible to the user (hidden, start/end time, fe_group), no
     * page above it may deny access and pass that on to its subpages (extendToSubpages),
     * and without a backend login no page in the root line may be a backend user section.
     *
     * @param array<int, int> $pageIds
     *
     * @return array<int, int>
     */
    private function filterByRootLineAccess(array $pageIds): array
    {
        $context         = GeneralUtility::makeInstance(Context::class);
        $voter           = GeneralUtility::makeInstance(RecordAccessVoter::class);
        $backendLoggedIn = (bool) $context->getPropertyFromAspect('backend.user', 'isLoggedIn', false);

        return array_values(array_filter(
            $pageIds,
            fn (int $pageId): bool => $this->isServedToCurrentUser($pageId, $voter, $context, $backendLoggedIn),
        ));
    }

    private function isServedToCurrentUser(int $pageId, RecordAccessVoter $voter, Context $context, bool $backendLoggedIn): bool
    {
        try {
            $rootLine = GeneralUtility::makeInstance(RootlineUtility::class, $pageId)->get();
        } catch (PageNotFoundException|RootLineException) {
            return false;
        }

        foreach ($rootLine as $page) {
            if (!$backendLoggedIn && (int) ($page['doktype'] ?? 0) === self::DOKTYPE_BACKEND_USER_SECTION) {
                return false;
            }

            $granted = (int) ($page['uid'] ?? 0) === $pageId
                ? $voter->accessGranted('pages', $page, $context)
                : $voter->accessGrantedForPageInRootLine($page, $context);

            if (!$granted) {
                return false;
            }
        }

        return true;
    }
}
