<!-- SPDX-License-Identifier: AGPL-3.0-or-later -->
<!-- SPDX-FileCopyrightText: Netresearch DTT GmbH -->
# Security assurance

What users of `nr_image_sitemap` can and cannot expect in terms of security, and the argument for it: which records and files end up in the public image sitemap, how the output is encoded, which configuration the extension trusts, the threat model, trust boundaries, the design principles applied and how common weaknesses are countered. Every claim names the file that implements it or the test that checks it. Components and data flow: [ARCHITECTURE.md](ARCHITECTURE.md). Vulnerability reporting: [SECURITY.md](../SECURITY.md).

The document describes the code on `main`. Statements about TYPO3 behaviour were read in TYPO3 14.3.7 (the version a `composer install` resolved on 2026-09-30) and in the tag `v13.4.35` of TYPO3 13.4, the two LTS versions the extension supports (`composer.json`).

## What the extension does, security-wise

The extension adds one sitemap to `typo3/cms-seo`: an XML list of image files, grouped by the page they appear on. It is rendered by a public frontend page type (`typeNum = 1642072014`, `Configuration/TypoScript/setup.typoscript`) that anyone who can reach the site can request, without logging in.

It only reads. `Classes/Domain/Repository/ImageFileReferenceRepository.php` runs `SELECT` queries on `sys_file_reference`, `sys_file`, `pages` and the tables a reference belongs to; the extension has no database table, no backend module, no controller, no route of its own, writes no files and makes no outgoing network requests (`Classes/`, `Configuration/`).

## What ends up in the sitemap

`ImagesXmlSitemapDataProvider::generateItems()` (`Classes/Seo/ImagesXmlSitemapDataProvider.php`) and `ImageFileReferenceRepository::findAllImages()` select a file reference only if all of the following hold:

| Condition | Where |
|-----------|-------|
| The reference sits on the configured root page (`rootPage`, else the site's root page) or on a page below it that the frontend user of the request may see. Pages below the root page come from `PageRepository::getPageIdsRecursive()` of TYPO3, which drops deleted, hidden, not yet started or expired pages and pages whose `fe_group` the user does not have, and does not descend below pages that hide their subpages (`extendToSubpages`) | `ImagesXmlSitemapDataProvider.php` |
| The page the reference sits on is not deleted, hidden, not yet started or expired. The join on `pages` is a `leftJoin()`, and the TYPO3 `QueryBuilder` puts its default restrictions for the joined table into the join condition, so such a page leaves `p.uid` empty and fails the `p.uid IN (…)` filter | `ImageFileReferenceRepository::getAllRecords()` |
| The reference itself is not deleted or hidden (the default restrictions of the query on `sys_file_reference`) | `getAllRecords()` |
| The reference is a live record (`t3ver_wsid = 0`); workspace versions are never listed | `getAllRecords()` |
| The file exists (`f.missing = 0`) and is of type image (`FileType::IMAGE`) | `getAllRecords()`, `generateItems()` |
| The reference belongs to one of the configured tables (`tables`, default `pages, tt_content`) and is in the language of the request | `getAllRecords()`, `Configuration/TypoScript/constants.typoscript` |
| The page's doktype is not excluded (`excludedDoktypes`, default `3, 4, 6, 7, 199, 254, 255`) and the page matches `additionalWhere` (default `no_index = 0 AND canonical_link = ''`, which leaves out pages marked "noindex" and pages with a canonical link to elsewhere) | `getAllRecords()`, `constants.typoscript` |
| The record the reference belongs to still exists and is neither deleted nor hidden, nor outside its start and end time | `ImageFileReferenceRepository::findRecordByForeignUid()` |

The references that pass are loaded as `ImageFileReference` models through an Extbase query (`findAllImages()`), which applies the frontend enable fields of `sys_file_reference` a second time.

`Tests/Functional/Domain/Repository/ImageFileReferenceRepositoryVisibilityTest.php` checks the repository part: from a page list that includes a hidden, a deleted and an expired page, only the live, visible reference on the visible page and the one from a visible content element are returned; hidden and deleted references, a workspace version, references on the three pages and references from hidden and deleted content elements are not.

For each selected reference the sitemap contains the absolute URL of the page (built by the TYPO3 `LinkFactory` and the site base from `SiteFinder`), the public URL of the file, and the title and description of the reference, falling back to the title and description of the file's metadata (`Classes/Domain/Model/ImageFileReference.php`).

## Output encoding

The sitemap is rendered by the Fluid template `Resources/Private/Templates/Sitemap/Images.xml`. Fluid escapes every variable it outputs with `htmlspecialchars()` unless a template disables it; this template disables it nowhere. `<loc>` goes through `f:format.htmlentities`. The title and the caption are text typed in by editors; `theImageSitemapEscapesTitlesAndCaptions` in `Tests/Functional/Seo/ImagesXmlSitemapTest.php` renders a title and a caption that contain markup, `&` and quotes, and checks that no element reaches the output, that the result parses as XML and that both values read back unchanged. The licence notice of the template is in `<f:comment>` and is not rendered, which the same test checks.

## Configuration the extension trusts

The four settings `rootPage`, `tables`, `excludedDoktypes` and `additionalWhere` come from TypoScript (`Configuration/TypoScript/constants.typoscript`, `setup.typoscript`, and the site set in `Configuration/Sets/ImageSitemap/`). The extension treats TypoScript as trusted integrator configuration:

- `additionalWhere` is a raw SQL fragment and is added to the query as it is (`getAllRecords()`, documented at `findAllImages()`). Whoever can change it can change the query.
- `tables` decides which tables' references are listed. The table names are bound as a parameter in the filter, and `findRecordByForeignUid()` uses a table name only after `tablesExist()` confirmed it, through the quoting `QueryBuilder::from()`.
- `rootPage` is cast to an integer and `excludedDoktypes` is split with `GeneralUtility::intExplode()` (`generateItems()`).

## Threat model

| Actor | Can | Cannot, and why |
|-------|-----|-----------------|
| Anonymous visitor or crawler | Request the sitemap page type and every page of it (`cms-seo` puts 1000 page entries on each) | Pass input into a query: the provider reads no query parameter; it uses the site and the normalised parameters TYPO3 attaches to the request, and the language of the TYPO3 context (`generateItems()`, `ImageFileReferenceRepository::getLanguageUid()`) |
| Logged-in frontend user | Request the sitemap; pages below the root page that their groups may see are included | See pages below the root page their groups may not see (`getPageIdsRecursive()`) |
| Editor | Put files on pages and content elements and give them titles and captions, which then appear in the sitemap | Inject markup or break the XML through a title or caption (Fluid escaping, test above) |
| Integrator or administrator with access to TypoScript | Change the four settings above, including raw SQL in `additionalWhere` | Nothing is enforced against this role: it is trusted |

## Trust boundaries

1. **Frontend request → sitemap rendering.** The request is anonymous or carries a frontend user. It selects the sitemap page type; everything the provider uses from it is a TYPO3 request attribute.
2. **TypoScript → query.** Configuration crosses into SQL: as bound parameters for `tables`, `excludedDoktypes` and the page list, as an integer for `rootPage`, and unchanged for `additionalWhere`.
3. **Database content → XML.** Titles, captions and URLs stored by editors or generated by TYPO3 cross into the public document through Fluid's escaping.

## Secure design principles applied

- **Least privilege.** The extension only reads, has no backend part and no endpoint of its own; it adds one frontend page type that `cms-seo` renders.
- **Economy of mechanism.** Selection is one query plus an existence check per reference; visibility rules come from the TYPO3 restriction containers and `PageRepository` instead of hand-written conditions, apart from the workspace and `missing` filters.
- **Defence in depth.** Page visibility is checked in the page list, for pages below the root page, and again in the restricted join; reference visibility is checked in the query and again in the Extbase query; the visibility of the record a reference belongs to is checked by the existence check.
- **Secure defaults.** The shipped constants exclude pages marked "noindex", pages with a canonical link, and the doktypes 3, 4, 6, 7, 199, 254 and 255: external links, shortcuts, the backend user section, mount points, spacers, folders and the recycler (`constants.typoscript`).
- **Fail safe.** A reference whose foreign table does not exist, whose record is gone, or whose file is missing is left out rather than listed (`findRecordByForeignUid()`, `getAllRecords()`).

## Common weaknesses

| Weakness | Counter | Evidence |
|----------|---------|----------|
| SQL injection (CWE-89) | Page IDs, file types, table names, doktypes, language and foreign UID are bound with `createNamedParameter()`; table names used as identifiers are checked with `tablesExist()` and quoted by `QueryBuilder`. `additionalWhere` is trusted configuration, see above | `ImageFileReferenceRepository.php` |
| XML/markup injection, XSS in sitemap consumers (CWE-79, CWE-91) | Fluid escaping of every output value | `Images.xml`, `theImageSitemapEscapesTitlesAndCaptions` |
| Exposure of unpublished content (CWE-200) | Hidden, deleted, timed and workspace records are filtered as listed above | `ImageFileReferenceRepositoryVisibilityTest` |
| Vulnerable dependencies (CWE-1395) | Composer Audit and Dependency Review run on every pull request (`CONTRIBUTING.md`, "Governance and policies") | `.github/workflows/checks.yml` |
| Unsafe code patterns in the extension | PHPStan with the rule sets of `netresearch/typo3-ci-workflows`, Rector and Opengrep run on every pull request | `Build/phpstan.neon`, `.github/workflows/ci.yml`, `checks.yml` |

## Security expectations

Users can expect:

- **Read-only operation.** The extension changes no data and calls no external service.
- **Only published content from the configured page tree.** Deleted, hidden, timed-out and workspace records are not listed, and pages below the root page are filtered for the frontend user of the request, as described above.
- **Well-formed, escaped XML** for any title or caption an editor enters.

Users cannot expect:

- **Protection against the TypoScript configuration.** `additionalWhere` is raw SQL, and `rootPage` and `tables` decide what becomes public. Review who can edit the site's TypoScript.
- **A sitemap scoped to the requesting site.** The shipped constant sets `rootPage = 1`; on an installation with several sites, set `rootPage` per site, or empty it so the site's root page is used (`generateItems()`).
- **Confidentiality of what is listed.** The sitemap is public by design. Every file that passes the filters is listed with its URL, title and caption; do not rely on the sitemap to hide files that are reachable on their own.
- **Bounded cost per request.** On each uncached request the provider loads all matching references and runs one existence query per reference before `cms-seo` splits the result into sitemap pages of 1000 page entries (`findAllImages()`).
