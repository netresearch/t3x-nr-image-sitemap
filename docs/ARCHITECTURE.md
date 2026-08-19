# Architecture

Agent-facing component map for `nr_image_sitemap`. Verified against the source on 2026-08-19 — if code and this file disagree, the code wins; fix this file in the same PR.

## System overview

The extension adds one sitemap type to `typo3/cms-seo`: an XML image sitemap. It contributes a `XmlSitemapDataProvider` implementation, the TypoScript/Site Set wiring that registers it under `plugin.tx_seo`, and a Fluid template that renders the collected image references as sitemap XML. There is no backend module, no controller, and no own database table — it reads `sys_file_reference`/`sys_file`/`pages`.

## Components

| Component | File | Role |
|-----------|------|------|
| Data provider | `Classes/Seo/ImagesXmlSitemapDataProvider.php` | Extends `AbstractXmlSitemapDataProvider` (cms-seo). Reads sitemap config (`tables`, `excludedDoktypes`, `additionalWhere`, `rootPage`), resolves the page tree, fetches images via the repository, groups them per page URL. Constructor signature is fixed by the cms-seo `XmlSitemapDataProviderInterface` contract; dependencies come via `GeneralUtility::makeInstance`. |
| Repository | `Classes/Domain/Repository/ImageFileReferenceRepository.php` | Extbase `Repository` combined with a direct DBAL `QueryBuilder` over `sys_file_reference` ⋈ `sys_file` ⋈ `pages` (filters: page list, file type, tablenames, workspace 0, current language, optional doktype exclusion and raw `additionalWhere`). Verifies the foreign record exists, then hydrates `ImageFileReference` models via an Extbase `in(uid, …)` query. |
| Domain model | `Classes/Domain/Model/ImageFileReference.php` | Extends Extbase `FileReference`; mapped to `sys_file_reference` in `Configuration/Extbase/Persistence/Classes.php`. `getTitle()`/`getDescription()` fall back to the underlying file's properties. |
| Rendering | `Resources/Private/Templates/Sitemap/Images.xml` | Fluid template producing the `<image:image>` sitemap XML. Registered as template `Sitemap/Images` for the provider. |
| Wiring (classic) | `Configuration/TypoScript/setup.typoscript` + `constants.typoscript` | Registers sitemap type `xmlImagesSitemap` under `plugin.tx_seo`, adds view paths, and clones `seo_sitemap` to `seo_sitemap_images` with `typeNum = 1642072014`. Static template registered via `Configuration/TCA/Overrides/sys_template.php`. |
| Wiring (site sets) | `Configuration/Sets/ImageSitemap/` | TYPO3 v13+ Site Set `netresearch/image-sitemap` (depends on `typo3/seo-sitemap`) carrying the same TypoScript. |
| DI | `Configuration/Services.yaml` | Autowires/autoconfigures everything under `Netresearch\NrImageSitemap\`. |

## Data flow

Frontend request with `typeNum=1642072014` → cms-seo sitemap rendering instantiates `ImagesXmlSitemapDataProvider` with the TypoScript `config` → provider resolves the root page (config override or site root) and its subtree via `PageRepository::getPageIdsRecursive` → `ImageFileReferenceRepository::findAllImages()` returns hydrated `ImageFileReference` models → provider groups them by page URI (md5 hash key) into `$this->items` → Fluid template `Sitemap/Images` renders the XML.

## Dependency rules

There is no architecture test suite (`Tests/Architecture/` does not exist). Observable layering: `Seo` depends on `Domain/Repository`, which depends on `Domain/Model`; nothing depends on `Seo`. Keep it that way — new query logic belongs in the repository, not the provider.

## Key decisions

- Constructor of the data provider cannot be changed (cms-seo interface contract); PHPStan suppressions for it live in `Build/phpstan.neon`.
- `FileType::IMAGE->value` (the int), not the enum instance, is passed to the repository — DBAL cannot bind enum instances in `PARAM_INT_ARRAY`.
- `additionalWhere` is a raw SQL fragment by design; callers are responsible for quoting (documented at `ImageFileReferenceRepository::findAllImages()`).
- Release tags carry no `v` prefix (`14.0.0`) — rationale documented in `.github/workflows/release.yml`.
