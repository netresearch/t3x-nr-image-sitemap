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

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function locationDataProvider(): array
    {
        return [
            'site-relative url gets the site url'       => ['fileadmin/user_upload/image.jpg', 'https://example.org/fileadmin/user_upload/image.jpg'],
            'root-relative url gets the site url'       => ['/fileadmin/user_upload/image.jpg', 'https://example.org/fileadmin/user_upload/image.jpg'],
            'absolute url of another host stays as is'  => ['https://cdn.example.net/images/image.jpg', 'https://cdn.example.net/images/image.jpg'],
            'scheme-relative url of another host stays' => ['//cdn.example.net/images/image.jpg', '//cdn.example.net/images/image.jpg'],
            'empty url stays empty'                     => ['', ''],
        ];
    }

    #[Test]
    #[DataProvider('locationDataProvider')]
    public function getLocationReturnsAnAbsoluteUrlWithoutDoublingTheHost(string $originalUrl, string $expected): void
    {
        $subject = new ImageFileReference();
        $subject->setOriginalResource($this->createOriginalResource(['getPublicUrl' => $originalUrl]));
        $subject->setSiteUrl('https://example.org/');

        self::assertSame($expected, $subject->getLocation());
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
