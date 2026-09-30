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

namespace Netresearch\NrImageSitemap\Tests\Functional\Domain\Repository;

use Netresearch\NrImageSitemap\Domain\Model\ImageFileReference;
use Netresearch\NrImageSitemap\Domain\Repository\ImageFileReferenceRepository;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Resource\FileType;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Pins which file references the repository hands to the sitemap: only live, visible
 * references on visible pages, attached to records that are neither hidden nor deleted.
 *
 * docs/SECURITY-ASSURANCE.md cites this test for those claims.
 */
final class ImageFileReferenceRepositoryVisibilityTest extends FunctionalTestCase
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

        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/Database/Visibility.csv');

        $this->subject = $this->get(ImageFileReferenceRepository::class);
    }

    /**
     * The page list deliberately contains the hidden, deleted and expired pages, so the
     * repository's own filtering is what keeps their references out.
     */
    #[Test]
    public function findAllImagesReturnsOnlyVisibleLiveReferences(): void
    {
        $result = $this->subject->findAllImages(
            [FileType::IMAGE->value],
            [1, 2, 3, 4],
            ['pages', 'tt_content'],
            [],
            '',
        );

        $uids = array_map(
            static fn (ImageFileReference $reference): int => $reference->getUid() ?? 0,
            $result,
        );
        sort($uids);

        self::assertSame(
            [1, 8],
            $uids,
            'Only the live reference on the visible page and the one from the visible content element may be returned.',
        );
    }
}
