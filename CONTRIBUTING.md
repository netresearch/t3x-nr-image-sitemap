<!-- SPDX-License-Identifier: AGPL-3.0-or-later -->
<!-- SPDX-FileCopyrightText: Netresearch DTT GmbH -->
# Contributing

Thank you for contributing! Pull requests are welcome — please open them against `main`.

## Commit Signing

All commits must be cryptographically signed and carry a DCO sign-off: `git commit -S --signoff`. The `require-signed-commits` ruleset on the default branch enforces the signature (the "Verified" badge on GitHub); the DCO check enforces the `Signed-off-by` trailer — these are two different things and both are required. Quickest setup is SSH signing: register your SSH key as a *signing key* on your GitHub account, then `git config --global gpg.format ssh && git config --global user.signingkey ~/.ssh/<key>.pub`.

## Conventions

Commits follow [Conventional Commits](https://www.conventionalcommits.org/) (`feat:`, `fix:`, `docs:`, ...). CI must be green before merge; the test and lint entry points are defined in `composer.json` and the `Makefile`.

## Governance and policies

This extension follows the organisation-wide Netresearch policies:

- [Governance](https://github.com/netresearch/.github/blob/main/GOVERNANCE.md): ownership, roles and their responsibilities, how decisions are made and how disagreements are resolved.
- [Roadmap](https://github.com/netresearch/.github/blob/main/ROADMAP.md): planned and excluded work for the next twelve months. It applies here because this repository has no roadmap of its own.
- [Handling of dependency and code analysis findings](https://github.com/netresearch/.github/blob/main/SECURITY.md#handling-of-dependency-and-code-analysis-findings): which vulnerability, licence and static-analysis findings must be fixed, by when, and how exceptions are recorded.
- [Secret management](https://github.com/netresearch/.github/blob/main/SECURITY.md#secret-management): where CI and release credentials are stored, who may use them, and when they are rotated.
- [Access roster](https://github.com/netresearch/.github/blob/main/docs/access-roster.md): the accounts with admin or write access to this repository.

Checks that run on every pull request in this repository:

- `.github/workflows/checks.yml`: Composer Audit (fails on any advisory for an installed package) and Opengrep SAST, both through `security.yml` of `netresearch/typo3-ci-workflows` (which Opengrep findings block a pull request is set by the organisation's [static analysis rule](https://github.com/netresearch/.github/blob/main/SECURITY.md#static-analysis-sast)); Dependency Review (fails on added dependencies with a vulnerability of severity high or higher); PHP licence check (`license-check.yml`, fails when a Composer dependency declares `SSPL`, `BSL` or `BUSL`, or an `SSPL-` or `BUSL-` identifier of any version, such as `SSPL-1.0` or `BUSL-1.1`; `BSL-1.0` passes); CodeQL, which analyses the workflow files only, as the repository has no JavaScript, TypeScript or Go; Betterleaks secret scanning; zizmor for the workflow files; `pr-quality`, which reports the size of the pull request and approves a non-draft pull request from a branch of this repository opened by an owner, member or collaborator. The `fuzz` job is called but runs nothing here, as the repository has no fuzz or mutation tests. The `All security checks` job fails when any of these jobs failed or was cancelled; it is one of the contexts the branch rules require.
- `.github/workflows/ci.yml`: PHP lint, code style (php-cs-fixer), PHPStan, Rector, and the unit and functional tests, for PHP 8.2 to 8.5 and TYPO3 13.4 and 14.3; the `ci / All CI checks` job summarises them for the branch rules.
- `.github/workflows/harness-verify.yml`: `Build/Scripts/verify-harness.sh` checks that `AGENTS.md` and `docs/` match the repository.
- `.github/workflows/check-template-drift.yml`: the workflow files managed by the organisation template have not drifted from it.
- `.github/workflows/labeler.yml` labels the pull request by the paths it changes, `.github/workflows/community.yml` greets the author of a first pull request and `.github/workflows/auto-merge-deps.yml` approves dependency update pull requests from Renovate or Dependabot and enables auto-merge for them, unless the pull request carries the `deps-major` or `deps-no-automerge` label; these three run on `pull_request_target` and check nothing.

No exception is recorded: `composer.json` has no `config.audit.ignore` entry.

The security properties of the extension itself are described in [docs/SECURITY-ASSURANCE.md](docs/SECURITY-ASSURANCE.md).
