<?php

/*
 * This file is part of the package netresearch/nr-image-sitemap.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\NrImageSitemap\Tests\Functional\Domain\Repository;

use Netresearch\NrImageSitemap\Domain\Model\ImageFileReference;
use Netresearch\NrImageSitemap\Domain\Repository\ImageFileReferenceRepository;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Resource\FileType;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Tests for the image file reference repository.
 */
final class ImageFileReferenceRepositoryTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'seo',
    ];

    protected array $testExtensionsToLoad = [
        'netresearch/nr-image-sitemap',
    ];

    private ImageFileReferenceRepository $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Database/FileReferences.csv');

        $this->subject = $this->get(ImageFileReferenceRepository::class);
    }

    /**
     * Regression test: FileType::IMAGE used to be passed as an enum instance into a
     * Connection::PARAM_INT_ARRAY binding. DBAL casts bound values to int, which turns any
     * object into 1 (plus a warning), so the query matched FileType::TEXT (1) records and
     * the image sitemap never contained an image.
     */
    #[Test]
    public function findAllImagesReturnsOnlyReferencesToImageFiles(): void
    {
        $result = $this->subject->findAllImages(
            [FileType::IMAGE->value],
            [1, 2],
            ['pages', 'tt_content'],
            [],
            '',
        );

        self::assertCount(2, $result);

        $uids = array_map(
            static fn (ImageFileReference $reference): int => $reference->getUid() ?? 0,
            $result,
        );
        sort($uids);

        self::assertSame([1, 2], $uids);
    }

    /**
     * Guards the actual defect: passing the enum instance instead of its value must not
     * silently return the text file reference.
     */
    #[Test]
    public function findAllImagesDoesNotReturnReferencesToTextFiles(): void
    {
        $result = $this->subject->findAllImages(
            [FileType::TEXT->value],
            [1, 2],
            ['pages', 'tt_content'],
            [],
            '',
        );

        self::assertCount(1, $result);
        self::assertSame(3, $result[array_key_first($result)]->getUid());
    }

    #[Test]
    public function findAllImagesRestrictsResultToTheGivenPageList(): void
    {
        $result = $this->subject->findAllImages(
            [FileType::IMAGE->value],
            [1],
            ['pages', 'tt_content'],
            [],
            '',
        );

        self::assertCount(1, $result);
        self::assertSame(1, $result[array_key_first($result)]->getUid());
    }

    #[Test]
    public function findAllImagesRestrictsResultToTheGivenTables(): void
    {
        $result = $this->subject->findAllImages(
            [FileType::IMAGE->value],
            [1, 2],
            ['pages'],
            [],
            '',
        );

        self::assertCount(1, $result);
        self::assertSame(1, $result[array_key_first($result)]->getUid());
    }

    #[Test]
    public function findAllImagesSkipsPagesWithAnExcludedDoktype(): void
    {
        $result = $this->subject->findAllImages(
            [FileType::IMAGE->value],
            [1, 2],
            ['pages', 'tt_content'],
            [254],
            '',
        );

        self::assertCount(1, $result);
        self::assertSame(1, $result[array_key_first($result)]->getUid());
    }

    #[Test]
    public function findAllImagesSkipsReferencesToMissingFiles(): void
    {
        $result = $this->subject->findAllImages(
            [FileType::IMAGE->value],
            [3],
            ['pages', 'tt_content'],
            [],
            '',
        );

        self::assertSame([], $result);
    }
}
