<!-- Managed by agent: keep sections and order; edit content, not structure. Last updated: 2026-08-19 -->

# AGENTS.md — workflows

<!-- AGENTS-GENERATED:START overview -->
## Overview
Every workflow here is a thin caller of a central reusable in `netresearch/.github` or `netresearch/typo3-ci-workflows`. Repo-local files carry only triggers, per-job `permissions`, and `with:` inputs; the actual steps, checkout pins, and harden-runner live in the reusables.
<!-- AGENTS-GENERATED:END overview -->

<!-- AGENTS-GENERATED:START filemap -->
## Key Files
| File | Purpose |
|------|---------|
| `ci.yml` | Test matrix (PHP 8.2-8.5 × TYPO3 ^13.4/^14.3, pins `typo3/cms-core` + `typo3/cms-seo` per leg) via `typo3-ci-workflows/ci.yml` — intentional-drift, customize here |
| `checks.yml` | Security/quality jobs + `All security checks` gate — byte-identical to the org template, drift-enforced, DO NOT edit locally |
| `check-template-drift.yml` | Fails CI when drift-governed files diverge from `netresearch/.github/templates/typo3-extension/` |
| `release.yml` | Tag-triggered GitHub release + TER publish; tags have NO `v` prefix (e.g. `14.0.0`) — intentional-drift |
| `auto-merge-deps.yml` | Auto-merge for Dependabot/Renovate PRs (`pull_request_target`, no head checkout) |
| `community.yml` | Stale-issue handling + first-contribution greeting |
| `labeler.yml` | PR labeling from `.github/labeler.yml` |
| `harness-verify.yml` | Agent-harness consistency check via `Build/Scripts/verify-harness.sh` |
<!-- AGENTS-GENERATED:END filemap -->

<!-- AGENTS-GENERATED:START setup -->
## Workflow files
- Drift governance: `.github/template.yaml` declares `template: typo3-extension`; only `ci.yml` and `release.yml` are listed as intentional-drift. Everything else must stay byte-identical to the template — change it upstream in `netresearch/.github`, not here. New files (not present in the template) are not byte-compared.
- Required checks on `main` (rulesets): `All security checks`, `CodeQL`, `DCO`, `Opengrep OSS`, `betterleaks`, `ci / All CI checks`, `scorecard`, `zizmor`.
<!-- AGENTS-GENERATED:END setup -->

<!-- AGENTS-GENERATED:START structure -->
## Directory structure
```
.github/
  template.yaml     → drift-governance manifest (template + intentional-drift list)
  dependabot.yml    → dependency update config
  labeler.yml       → label rules used by labeler workflow
  workflows/        → thin callers only (see Key Files above)
```
<!-- AGENTS-GENERATED:END structure -->

<!-- AGENTS-GENERATED:START code-style -->
## Workflow conventions
- **Thin callers**: no repo-local `run:` step logic except the gate in `checks.yml`; extend the central reusable instead of inlining steps
- **Explicit `permissions`**: top-level `permissions: {}` (or `contents: read`), each `uses:` job grants exactly the reusable's caller contract
- **Pin direct action uses** to a full commit SHA with a version comment (see harden-runner in `checks.yml`)
- **`pull_request_target` needs a zizmor ignore comment** (`# zizmor: ignore[dangerous-triggers]`) plus a prose justification why no PR head code runs
- **Gate discipline**: any job added to `checks.yml` MUST also be added to `gate.needs` — a job missing there cannot block a merge and nothing catches the loss (but remember: `checks.yml` changes go through the org template)
<!-- AGENTS-GENERATED:END code-style -->

<!-- AGENTS-GENERATED:START patterns -->
## Common patterns

### Thin caller of a central reusable (the only pattern used here)
```yaml
permissions: {}

jobs:
  ci:
    uses: netresearch/typo3-ci-workflows/.github/workflows/ci.yml@main
    permissions:
      contents: read
    with:
      php-versions: '["8.2","8.3","8.4","8.5"]'
      typo3-versions: '["^13.4","^14.3"]'
      typo3-packages: '["typo3/cms-core","typo3/cms-seo"]'
```
Both core packages must be pinned per matrix leg — with only `typo3/cms-core` constrained, `typo3/cms-seo` could drift to the other major.
<!-- AGENTS-GENERATED:END patterns -->

<!-- AGENTS-GENERATED:START security -->
## Security & safety
- **Never** widen a `pull_request_target` workflow to check out or execute PR head code — the existing ones are safe precisely because they don't
- **Pass secrets explicitly** (`secrets:` block, e.g. `CODECOV_TOKEN`, `TYPO3_TER_ACCESS_TOKEN`); never `secrets: inherit`
- **Minimal permissions**: start from `permissions: {}` and add per job only what the reusable's contract requires
- **zizmor and CodeQL scan these files** — both are required checks; a finding here blocks every PR
<!-- AGENTS-GENERATED:END security -->

<!-- AGENTS-GENERATED:START checklist -->
## PR/commit checklist
- [ ] Change belongs here — drift-governed files (`checks.yml`, `community.yml`, ...) are edited in `netresearch/.github/templates/typo3-extension/`, only `ci.yml`/`release.yml` in-repo
- [ ] `permissions` blocks are minimal and per-job
- [ ] Any direct `uses:` step pinned to a full SHA with version comment
- [ ] `pull_request_target` additions carry the zizmor ignore comment + justification
- [ ] Release tags follow the repo's no-`v`-prefix scheme (`14.0.0`) — `release.yml` matches both spellings for safety
<!-- AGENTS-GENERATED:END checklist -->

<!-- AGENTS-GENERATED:START examples -->
## Patterns to Follow
> **Prefer looking at real code in this repo over generic examples.**
> `ci.yml` (matrix caller with inline rationale comments) and `checks.yml` (gate pattern with its full design rationale in comments) are the reference implementations.
<!-- AGENTS-GENERATED:END examples -->

<!-- AGENTS-GENERATED:START help -->
## When stuck
- Central reusables: https://github.com/netresearch/.github and https://github.com/netresearch/typo3-ci-workflows
- Template source for drift-governed files: `netresearch/.github/templates/typo3-extension/`
- GitHub Actions workflow syntax: https://docs.github.com/en/actions/reference/workflow-syntax-for-github-actions
- The long comments inside `checks.yml` and `release.yml` document the non-obvious decisions (gate vs. required checks, merge-queue behavior, tag scheme)
<!-- AGENTS-GENERATED:END help -->
