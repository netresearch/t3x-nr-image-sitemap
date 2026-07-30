# 14.0.0

TYPO3 v14.3 LTS support. The v13 line stays supported: this release requires
`typo3/cms-core` and `typo3/cms-seo` `^13.4 || ^14.3`, so no separate v14 branch is needed.

## BREAKING

- Minimum TYPO3 version raised from 13.0 to 13.4. TYPO3 13.0 - 13.3 are no longer supported.
- `ImageFileReference::getPublicUrl()` now returns the site-relative file URL
  (`fileadmin/image.jpg`) instead of an absolute URL. The absolute URL is composed in the
  sitemap template from the new `item.baseUrl` variable, which the data provider reads from
  the PSR-7 request. Overridden `Sitemap/Images` templates must render
  `{item.baseUrl}{image.publicUrl}` instead of `{image.publicUrl}`. The rendered
  `<image:loc>` value is unchanged.
- `Configuration/TypoScript/constants.txt` and `setup.txt` were renamed to
  `constants.typoscript` / `setup.typoscript`. The static template include is unaffected,
  but any `@import` of the old paths must be updated. TYPO3 v14 only resolves
  `.typoscript` in `@import`.
- `ImageFileReferenceRepository::findAllImages()` expects `FileType` case *values*
  (`int`) instead of `FileType` instances.

## FEATURE

- Added the site set `netresearch/image-sitemap`, so the TypoScript can be loaded without a
  `sys_template` record. It depends on `typo3/seo-sitemap`, which exists in both TYPO3 13.4
  and 14.3. The static template *Netresearch: Image Sitemap* keeps working: the
  `sys_template` include path still resolves `.typoscript` files.

## BUGFIX

- The image sitemap never contained any images. `FileType::IMAGE` was passed as an enum
  instance into a `Connection::PARAM_INT_ARRAY` binding; DBAL casts the bound value to int,
  which for an object emits `Object of class ... could not be converted to int` and yields
  `1`, so `f.type IN (1)` matched text files instead of images. The enum case value
  (`FileType::IMAGE->value`) is now passed instead. Regression-tested in
  `Tests/Functional/Domain/Repository/ImageFileReferenceRepositoryTest.php`.

## TASK

- Replaced the deprecated `GeneralUtility::getIndpEnv('TYPO3_SITE_URL')` call
  (deprecated in TYPO3 v14.3, removed in v15) with its documented replacement
  `NormalizedParams::getSiteUrl()`, read from the PSR-7 request in the data provider.
- Declared `extra.typo3/cms.version` and `extra.typo3/cms.Package.providesPackages` in
  `composer.json`. TYPO3 14.3 deprecated `ext_emconf.php` (#108345) and emits
  `E_USER_DEPRECATED` on every cache warm-up while both fields are missing. The file itself
  is kept: it stays the version source for the TER release pipeline and for classic-mode
  installs on TYPO3 13.4. The version in both places must stay in sync.
- Added a unit and a functional test suite (`composer ci:test:php:unit` /
  `composer ci:test:php:functional`) and enabled both in CI. The functional suite renders
  the sitemap through a real frontend request, so the site set, the
  `XmlSitemapDataProviderInterface` contract and the emitted `<image:loc>` values are
  covered end to end.
- Added a TYPO3 14.3 leg to the CI matrix and pinned both `typo3/cms-core` and
  `typo3/cms-seo` per matrix leg.
- Raised the Rector TYPO3 level set to `UP_TO_TYPO3_14` and added `Tests/` to the Rector and
  PHPStan paths.

## Contributors

- Sebastian Mendel

# 13.0.6

## TASK

- 39bcec0 [TASK] Update ext_emconf.php to load in TER via tailor

## Contributors

- Gitsko

# 13.0.5

## TASK

- 308dc7a [TASK] Update publish-to-ter.yml

## Contributors

- Gitsko

# 13.0.4

## TASK

- 50b682e [TASK] Fix github action

## Contributors

- Gitsko

# 13.0.2

## TASK

- 1db4bc6 [TASK] Fix version dependencies

## Contributors

- Gitsko

# 13.0.1

# 13.0.0

## MISC

- 648c6f9 NEXT-77: Merge two docblocks
- 6a8db2f typo
- 216b52e NEXT-77: Use depency injection and constructor property promotion, fix phpstan errors
- 3557f35 NEXT-77: Use standard timestamp format in ImagesXmlSitemapDataProvider
- ea1d6be NEXT-77: Remove redundant annotations in ImageFileReferenceRepository and ImagesXmlSitemapDataProvider
- a04532c NEXT-77: Upgrade to TYPO3 v13 and update dependencies
- f76ef32 NEXT-45: Removing redundant annotations
- 885a3d8 NEXT-45: Add AGPL license
- 3ed24ab NEXT-45: Add phpstan-report.xml and .gitlab.ci.yml to .gitignore
- f9f83f8 NEXT-45: Merge redundant commits for TYPO3 v11/v12 upgrade and rebase with main
- 4be5231 Update dependency ubuntu to v24
- 7eb4511 Update actions/checkout action to v4
- 7872d7f Add renovate.json
- 7858c32 NEXT-65: Correct composer json
- 95233cb NEXT-46: Fix pipeline to push into TER
- 7c28bab NEXT-46: Add pipeline to push into TER
- 5ce59df TYPO-7818: Allow configuration of the sitemap provider to better control the elements included in the sitemap
- ba14ca1 Initial commit

## Contributors

- Rico Sonntag
- Sebastian Altenburg
- Sebastian Koschel
- Sebastian Mendel
- renovate[bot]

