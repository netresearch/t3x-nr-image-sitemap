<?php

/*
 * This file is part of the package netresearch/nr-image-sitemap.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\NrImageSitemap\Tests\Unit\Domain\Model;

use Netresearch\NrImageSitemap\Domain\Model\ImageFileReference;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileReference;

/**
 * Tests for the image file reference domain model.
 */
final class ImageFileReferenceTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function publicUrlDataProvider(): array
    {
        return [
            'relative url is passed through'        => ['fileadmin/user_upload/image.jpg', 'fileadmin/user_upload/image.jpg'],
            'leading slash is stripped'             => ['/fileadmin/user_upload/image.jpg', 'fileadmin/user_upload/image.jpg'],
            'multiple leading slashes are stripped' => ['//fileadmin/image.jpg', 'fileadmin/image.jpg'],
            'empty url stays empty'                 => ['', ''],
        ];
    }

    /**
     * The model must not prefix a site URL any more: that used to be read from the
     * deprecated GeneralUtility::getIndpEnv('TYPO3_SITE_URL') and is now added by
     * the data provider, which has access to the PSR-7 request.
     */
    #[Test]
    #[DataProvider('publicUrlDataProvider')]
    public function getPublicUrlReturnsSiteRelativeUrlWithoutLeadingSlash(string $originalUrl, string $expected): void
    {
        $subject = new ImageFileReference();
        $subject->setOriginalResource($this->createOriginalResource(['getPublicUrl' => $originalUrl]));

        self::assertSame($expected, $subject->getPublicUrl());
    }

    #[Test]
    public function getPublicUrlReturnsEmptyStringWhenTheResourceHasNoPublicUrl(): void
    {
        $subject = new ImageFileReference();
        $subject->setOriginalResource($this->createOriginalResource(['getPublicUrl' => null]));

        self::assertSame('', $subject->getPublicUrl());
    }

    #[Test]
    public function getTitleFallsBackToTheFilePropertyWhenTheReferenceHasNoOwnTitle(): void
    {
        $subject = new ImageFileReference();
        $subject->setOriginalResource(
            $this->createOriginalResource([
                'hasProperty' => true,
                'getProperty' => 'Title from file',
            ]),
        );

        self::assertSame('Title from file', $subject->getTitle());
    }

    #[Test]
    public function getDescriptionFallsBackToTheFilePropertyWhenTheReferenceHasNoOwnDescription(): void
    {
        $subject = new ImageFileReference();
        $subject->setOriginalResource(
            $this->createOriginalResource([
                'hasProperty' => true,
                'getProperty' => 'Caption from file',
            ]),
        );

        self::assertSame('Caption from file', $subject->getDescription());
    }

    /**
     * getOriginalFile() is stubbed explicitly because TYPO3 v13.4 declares it without a
     * return type, so PHPUnit cannot generate a return value for it, and
     * FileReference::setOriginalResource() dereferences the result.
     *
     * @param array<string, mixed> $returnValues
     */
    private function createOriginalResource(array $returnValues): FileReference
    {
        $originalResource = self::createStub(FileReference::class);
        $originalResource->method('getOriginalFile')->willReturn(self::createStub(File::class));

        foreach ($returnValues as $method => $returnValue) {
            $originalResource->method($method)->willReturn($returnValue);
        }

        return $originalResource;
    }
}
