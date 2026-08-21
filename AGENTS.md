<!-- FOR AI AGENTS - Human readability is a side effect, not a goal -->
<!-- Managed by agent: keep sections and order; edit content, not structure -->
<!-- Last updated: 2026-08-19 | Last verified: 2026-08-19 -->

# AGENTS.md

**Precedence:** the **closest `AGENTS.md`** to the files you're changing wins. Root holds global defaults only.

TYPO3 extension `nr_image_sitemap` (`netresearch/nr-image-sitemap`): an image-sitemap XmlSitemapDataProvider for `typo3/cms-seo`. Supports TYPO3 ^13.4 / ^14.3 on PHP ^8.2 (version: see `ext_emconf.php`). Component map: `docs/ARCHITECTURE.md`.

## Commands
> Source: `composer.json` scripts (mirrored by `Makefile` targets). Run `composer install` first — tooling comes from the `netresearch/typo3-ci-workflows` dev dependency; binaries land in `.Build/bin/`, not `vendor/bin/`.

<!-- AGENTS-GENERATED:START commands -->
| Task | Command |
|------|---------|
| PHP lint | `composer ci:test:php:lint` |
| Code style (dry-run) | `composer ci:test:php:cgl` (= `make cgl`) |
| Code style (fix) | `composer ci:cgl` (= `make cgl-fix`) |
| PHPStan | `composer ci:test:php:phpstan` (= `make phpstan`) |
| Rector (dry-run) | `composer ci:test:php:rector` (= `make rector`) |
| Unit tests | `composer ci:test:php:unit` |
| Functional tests | `composer ci:test:php:functional` (needs a database; CI runs these in the reusable matrix) |
| Everything | `composer ci:test` |
<!-- AGENTS-GENERATED:END commands -->

> If commands fail, verify against `composer.json`/`Makefile` and update this table in the same PR.

## Response Style
- Answer first, elaborate only if needed. No sycophantic openers ("Great question!", "Absolutely!").
- For yes/no or status questions, lead with the answer.
- Skip preamble. Match response length to task complexity.

## Workflow
1. **Before coding**: Read nearest `AGENTS.md` + check Golden Samples for the area you're touching
2. **After each change**: Run the smallest relevant check (lint → phpstan → unit tests)
3. **Before committing**: Run full test suite if changes affect >2 files or touch shared code
4. **Before claiming done**: Run verification and **show output as evidence** — never say "try again", "should work now", "tested", "verified", or "all green" without pasted command output in the same turn

## File Map
<!-- AGENTS-GENERATED:START filemap -->
```
Classes/         → PHP source, PSR-4 Netresearch\NrImageSitemap\ (Domain/Model, Domain/Repository, Seo)
Configuration/   → Services.yaml, TypoScript, Site Set (Sets/ImageSitemap), TCA override, Icons, Extbase persistence
Resources/       → Fluid sitemap template (Private/Templates/Sitemap/Images.xml) + extension icon
Tests/           → PHPUnit tests (Unit/, Functional/ + Functional/Fixtures)
Build/           → tool configs (php-cs-fixer, phpstan, rector, phplint, phpunit XMLs) + Scripts/verify-harness.sh
docs/            → agent-facing docs: ARCHITECTURE.md, exec-plans/
```
<!-- AGENTS-GENERATED:END filemap -->

## Golden Samples (follow these patterns)
<!-- AGENTS-GENERATED:START golden-samples -->
| For | Reference | Key patterns |
|-----|-----------|--------------|
| Functional test | `Tests/Functional/Seo/ImagesXmlSitemapTest.php` | testing-framework setup, CSV fixtures from `Tests/Functional/Fixtures/Database/` |
| Unit test | `Tests/Unit/Domain/Model/ImageFileReferenceTest.php` | plain UnitTestCase structure |
| DB access | `Classes/Domain/Repository/ImageFileReferenceRepository.php` | QueryBuilder with named int/string array parameters |
<!-- AGENTS-GENERATED:END golden-samples -->

## Heuristics (quick decisions)
<!-- AGENTS-GENERATED:START heuristics -->
| When | Do |
|------|-----|
| Adding a class | PSR-4 under `Classes/`; `Configuration/Services.yaml` autowires `Netresearch\NrImageSitemap\` |
| Changing sitemap content/selection | `ImageFileReferenceRepository` (query) or `ImagesXmlSitemapDataProvider` (assembly) — see `docs/ARCHITECTURE.md` |
| Changing sitemap XML output | `Resources/Private/Templates/Sitemap/Images.xml` |
| Changing TypoScript options | Update BOTH `Configuration/TypoScript/` and the Site Set in `Configuration/Sets/ImageSitemap/` |
| Raising PHP/TYPO3 floors | Update `composer.json`, `ext_emconf.php` constraints AND the `ci.yml` matrix in the same PR |
| Running tasks | `make help` lists targets; composer scripts are the source of truth |
| Committing | Conventional Commits (feat:, fix:, docs:, ...), signed + sign-off (see CONTRIBUTING.md) |
| Adding dependency | Ask first - we minimize deps |
<!-- AGENTS-GENERATED:END heuristics -->

## Repository Settings
<!-- AGENTS-GENERATED:START repo-settings -->
- **Default branch:** `main`
- **Merge strategy:** merge
- **Signed commits:** required
- **Required checks (rulesets):** `All security checks`, `CodeQL`, `DCO`, `Opengrep OSS`, `betterleaks`, `ci / All CI checks`, `scorecard`, `zizmor`
- **Active rulesets:** Copilot review for default branch, require-signed-commits, t3x-baseline, t3x-pull-request
<!-- AGENTS-GENERATED:END repo-settings -->

## Boundaries

### Always Do
- Run pre-commit checks before committing
- Add tests for new code paths
- Use conventional commit format: `type(scope): subject`
- Use **atomic commits** (one logical change per commit); preserve signatures, keep bisection useful
- **Show test output as evidence before claiming work is complete** — never say "try again", "should work now", "tested", "verified", or "all green" without pasted command output
- Before any edit, verify `pwd` resolves inside the intended repo worktree — not `.bare/`, not `~/.claude/skills/…`, not `~/.claude/plugins/cache/…` (those are read-only caches that get clobbered on update)
- For upstream dependency fixes: run **full** test suite, not just affected tests
- Force-push only with `--force-with-lease`
- Follow PSR-12 coding standards and PHP ^8.2 features

### Ask First
- Adding new dependencies
- Modifying CI/CD configuration (`.github/workflows/AGENTS.md` explains which files are drift-enforced)
- Changing public API signatures
- Repo-wide refactoring or rewrites
- Operations that touch >3 repos (produce a dry-run plan first)

### Never Do
- Commit secrets, credentials, or sensitive data
- Modify `.Build/`, vendor, or generated files
- Push directly to `main` — open a PR
- Merge a PR before all review threads are resolved
- Squash commits during merge or rebase unless the user explicitly asked
- Edit installed skill/plugin cache paths (`~/.claude/skills/`, `~/.claude/plugins/cache/`, `**/.bare/**`) — always the source worktree
- Reply to review comments with bare "Addressed" or "Fixed" — cite the resolving commit SHA
- Commit a `composer.lock` — this extension deliberately ships none
- Edit `.github/workflows/checks.yml` locally — it is byte-identical to the org template and drift-enforced

## Contributing (for AI agents)
- **Comprehension**: Understand the problem before submitting code. Read the linked issue, understand *why* the change is needed, not just *what* to change.
- **Context**: Every PR must explain the trade-offs considered and link to the issue it addresses. Disclose AI assistance if the project requires it.
- **Continuity**: Respond to review feedback. Drive-by PRs without follow-up will be closed.

## Scoped AGENTS.md (MUST read when working in these directories)
<!-- AGENTS-GENERATED:START scope-index -->
- `./.github/workflows/AGENTS.md` — GitHub Actions workflows and CI/CD automation
<!-- AGENTS-GENERATED:END scope-index -->

> **Agents**: When you read or edit files in a listed directory, you **must** load its AGENTS.md first. It contains directory-specific conventions that override this root file.

## When instructions conflict
The nearest `AGENTS.md` wins. Explicit user prompts override files.
- For PHP-specific patterns, follow PSR standards
