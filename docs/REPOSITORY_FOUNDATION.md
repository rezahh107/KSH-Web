# Repository Foundation Record

## Provenance

This repository foundation was prepared by applying the current Repository Foundation domain from:

`rezahh107/Personal-Preference-Decision-Model`

Inspected authority snapshot:

`main@baad152c841713c703f0f56ae60eeff120c6ef50`

Key source modules consulted:

- `knowledge/current/domains/repository-foundation/INDEX.md`
- `principles-and-boundaries.md`
- `foundation-standard.md`
- `engineering-feedback-loop.md`
- `github-governance-and-security.md`
- `ai-agent-readiness.md`
- `application-and-validation.md`
- `knowledge/current/defaults/DEF-REPO-01.md`
- `knowledge/current/domains/software-delivery.md`

The standard was adapted to this repository; its file tree was not copied.

## Target inspected

Initial target state:

`rezahh107/KSH-Web@9e768c0111d709300c7c24859bd06330acd68762`

The repository initially contained only:

- `README.md`
- `.gitattributes`

## Foundation intent

North Star:

`SELF-EXPLAINING + SELF-VALIDATING`

Applied procedure:

`Inspect → Applicability → Smallest Sufficient Delta → Validate`

## Applicability record

| Capability | Classification | Target decision | Result in this foundation |
|---|---|---|---|
| Project entrypoint / README | REQUIRED | APPLICABLE | Expanded with project pointers, status, and verification entrypoint. |
| Ignore policy | CONDITIONAL | APPLICABLE | Added minimal secret/runtime/editor ignore rules. |
| `.gitattributes` | CONDITIONAL | APPLICABLE | Preserved text normalization and added binary design-asset handling. |
| `.editorconfig` | RECOMMENDED | APPLICABLE | Added basic UTF-8/LF/editor consistency. |
| Scope/charter | RECOMMENDED | APPLICABLE | Added `docs/MOTHER_PROJECT.md`. |
| Architecture record | CONDITIONAL | APPLICABLE | Added ADRs for the selected Kanoon article-list integration and the bounded plugin Release System. |
| Setup/build/lint/test docs | REQUIRED when applicable | APPLICABLE | Repository now has production plugin code plus proven canonical verification/build contracts. |
| Coherent verification contract | REQUIRED | APPLICABLE | `scripts/verify-foundation.sh` remains canonical and now also exercises Release System contract tests. |
| CI | CONDITIONAL | APPLICABLE | Read-only `Foundation Verify` reuses the canonical verification script; production publication is a separate explicit Owner-triggered workflow. |
| Cross-agent instructions | CONDITIONAL | APPLICABLE | Added concise root `AGENTS.md` because AI-assisted engineering is an explicit use case. |
| UI/design authority artifact | CONDITIONAL | APPLICABLE | Added a repository-owned responsive reference plus interpretation contract. |
| PR integration boundary | CONDITIONAL | APPLICABLE | Material work is delivered through reviewable branches/PRs; release publication binds a reviewed PR candidate to integrated source. |
| CODEOWNERS | CONDITIONAL | NOT_APPLICABLE now | Single-owner/simple topology; no concrete failure solved. |
| Issue forms/templates | CONDITIONAL | NOT_APPLICABLE now | No issue-intake volume/problem justifies them. |
| Git LFS | CONDITIONAL | NOT_APPLICABLE now | Current design asset is small after repository-friendly WebP export. |
| Dependency update/scanning automation | CONDITIONAL | NOT_APPLICABLE yet | No managed production dependency stack has been established. |
| Release/versioning machinery | CONDITIONAL | APPLICABLE | `KSH Kanoon Articles` is a real Release Unit with version authority, canonical ZIP builder/validator, GitHub Release distribution, explicit publication and last-mile verification. |
| License | CONDITIONAL | OWNER_DECISION_REQUIRED | Intentionally not invented. |
| Branch/ruleset protection | CONDITIONAL | NOT_INSPECTED / not changed | Requires a separate platform-governance decision; workflow presence is not enforcement. |

## Verification boundary

Current canonical command:

```bash
bash scripts/verify-foundation.sh
```

The check validates:

- required foundation/release-system documents and scripts exist and are non-empty;
- the imported design reference matches its recorded Git blob identity;
- key README/AGENTS authority pointers exist;
- obvious secret/runtime files are not tracked;
- PHP syntax for owned PHP code;
- retained Elementor historical-artifact contracts;
- KSH Kanoon Articles release version/builder/archive/release-notes contracts;
- WordPress Coding Standards and deterministic plugin regression suites.

The accompanying `Foundation Verify` GitHub Actions workflow executes the same contract on pull requests and pushes to `main`.

### Claim ceiling

A passing foundation check proves only the checked repository/static/deterministic contracts for that exact Head. It does not prove:

- authentic WordPress lifecycle/runtime behavior not exercised by the tests;
- source-site connectivity from the real host;
- future Kanoon DOM compatibility;
- future WP-Cron execution;
- production publication unless the explicit Publish workflow actually ran;
- post-publication consumer artifact correctness unless the published asset was retrieved and verified.

Those claims require their corresponding runtime/distribution evidence.

## Release-system adaptation

The original foundation intentionally deferred release machinery because no release/distribution contract existed. That premise changed after `KSH Kanoon Articles v0.4.0` became the first official GitHub Release.

The adopted release design is recorded in:

`docs/decisions/ADR-002-ksh-kanoon-articles-release-system.md`

The Release Unit is the plugin, not the whole site repository. A release-intended PR acts as the reviewed candidate. After merge, the Owner explicitly dispatches `Publish KSH Kanoon Articles` with the merged PR number. Publication fails closed unless exact-head PR qualification, plugin-subtree candidate/integrated identity, version/release-notes consistency, artifact verification and conflicting-release checks all pass. The workflow then publishes and re-downloads the actual GitHub Release asset before reporting `PUBLISHED_AND_VERIFIED`.

The release implementation does not mutate the already-published v0.4.0 history and does not introduce automatic version bumping or continuous publication.

## Future adaptation rule

When real code/tooling enters or evolves in the repository:

1. inspect the actual stack;
2. preserve healthy mechanisms;
3. extend the canonical verification contract with applicable build/lint/static-analysis/test/runtime checks;
4. update `AGENTS.md` to point to proven commands when the normal agent workflow changes;
5. do not create parallel human/agent/CI verification semantics when one underlying contract can be shared.
