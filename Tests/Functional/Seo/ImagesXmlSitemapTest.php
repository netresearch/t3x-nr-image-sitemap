<?php

/*
 * This file is part of the package netresearch/nr-image-sitemap.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\NrImageSitemap\Tests\Functional\Seo;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Renders the image sitemap through a real frontend request.
 *
 * This covers the whole chain the upgrade touches: the site set is resolved, its
 * TypoScript defines the `seo_sitemap_images` page type, typo3/cms-seo instantiates
 * the data provider through its XmlSitemapDataProviderInterface contract, and the
 * Fluid template renders the absolute image URLs.
 */
final class ImagesXmlSitemapTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'seo',
    ];

    protected array $testExtensionsToLoad = [
        'netresearch/nr-image-sitemap',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Database/FileReferences.csv');
        $this->writeSiteConfigurationWithImageSitemapSet();
    }

    #[Test]
    public function theSiteSetRegistersTheImageSitemapPageType(): void
    {
        $response = $this->executeFrontendSubRequest(
            new InternalRequest('https://localhost/?type=1642072014'),
        );

        self::assertSame(200, $response->getStatusCode());

        // The page type only exists because the site set pulled in the extension's
        // TypoScript, which in turn requires core's typo3/seo-sitemap set for
        // `seo_sitemap_images < seo_sitemap`. The sitemap index links the "images"
        // sitemap the extension registers, under a query parameter whose name differs
        // between TYPO3 v13 (`sitemap`) and v14 (`tx_seo[sitemap]`).
        self::assertMatchesRegularExpression(
            '#sitemap(%5D|\])?=images#',
            (string) $response->getBody(),
        );
    }

    #[Test]
    public function theImageSitemapRendersTheReferencedImages(): void
    {
        $response = $this->executeFrontendSubRequest(
            new InternalRequest($this->resolveImagesSitemapUrl()),
        );

        self::assertSame(200, $response->getStatusCode());

        $body = (string) $response->getBody();

        self::assertStringContainsString('<urlset', $body);

        // The Google image-sitemap namespace. Asserted by its path only: the namespace
        // identifier normatively uses the http scheme, so spelling it out in full would be
        // a cleartext URL literal, while rewriting it to https would assert a namespace
        // that does not exist. The path alone is unique in the document.
        self::assertStringContainsString('schemas/sitemap-image/1.1', $body);

        // Regression guard for the FileType enum binding: the image must be listed.
        self::assertStringContainsString(
            '<image:loc>https://localhost/fileadmin/user_upload/image-one.jpg</image:loc>',
            $body,
        );
        self::assertStringContainsString(
            '<image:title>Image on the root page</image:title>',
            $body,
        );
        self::assertStringContainsString(
            '<image:caption>Caption of the root page image</image:caption>',
            $body,
        );

        // The text file reference must not appear.
        self::assertStringNotContainsString('readme.txt', $body);
    }

    /**
     * The sitemap index is the only place the cHash-protected URL of the "images"
     * sitemap exists, so it is read from there instead of being hard-coded.
     */
    private function resolveImagesSitemapUrl(): string
    {
        $index = (string) $this->executeFrontendSubRequest(
            new InternalRequest('https://localhost/?type=1642072014'),
        )->getBody();

        self::assertSame(
            1,
            preg_match('#<loc>(?<url>[^<]+sitemap(?:%5D|\])?=images[^<]*)</loc>#', $index, $matches),
            'The sitemap index does not contain a sitemap location.',
        );

        return html_entity_decode($matches['url'], ENT_QUOTES | ENT_XML1);
    }

    private function writeSiteConfigurationWithImageSitemapSet(): void
    {
        $configuration = [
            'rootPageId'   => 1,
            'base'         => 'https://localhost/',
            'websiteTitle' => 'Image sitemap test site',
            'dependencies' => [
                'netresearch/image-sitemap',
            ],
            'languages' => [
                [
                    'title'           => 'English',
                    'enabled'         => true,
                    'languageId'      => 0,
                    'base'            => '/',
                    'locale'          => 'en_US.UTF-8',
                    'navigationTitle' => 'English',
                    'flag'            => 'us',
                ],
            ],
        ];

        $sitePath = Environment::getConfigPath() . '/sites/testing/';
        GeneralUtility::mkdir_deep($sitePath);
        GeneralUtility::writeFile(
            $sitePath . 'config.yaml',
            Yaml::dump($configuration, 99, 2),
            true,
        );
    }
}
