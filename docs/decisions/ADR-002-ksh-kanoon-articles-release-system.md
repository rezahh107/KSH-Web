# ADR-002 — KSH Kanoon Articles Release System

## Status

Accepted for implementation.

## Context

`KSH-Web` currently contains one independently releasable WordPress plugin: `KSH Kanoon Articles` under `wp-content/plugins/ksh-kanoon-articles/`. The first official plugin release (`ksh-kanoon-articles-v0.4.0`) proved the product/distribution contract but required manual artifact construction, checksum handling, tag creation, asset upload and post-publication verification.

The repository already has one canonical source qualification path: `bash scripts/verify-foundation.sh`, executed by `.github/workflows/foundation-verify.yml` on exact PR heads and `main`.

## Decision

### Release Unit

The Release Unit is **KSH Kanoon Articles**, not the whole `KSH-Web` repository.

Release identity:

- version authority: the WordPress plugin header `Version` in `ksh-kanoon-articles.php`;
- synchronized mirror: `Plugin::VERSION`;
- tag: `ksh-kanoon-articles-vX.Y.Z`;
- GitHub Release title: `KSH Kanoon Articles vX.Y.Z`;
- artifact: `ksh-kanoon-articles-vX.Y.Z.zip`;
- distribution channel: GitHub Releases.

### Reviewed candidate and integrated-source binding

A normal release-intended PR is the reviewed Release Candidate. A separate automated Release PR is not introduced at this stage.

Publication is an explicit Owner-triggered GitHub Actions operation receiving only the merged PR number. The workflow resolves the exact PR Head and GitHub integrated commit, then compares the Git tree identity of `wp-content/plugins/ksh-kanoon-articles` at both revisions. Publication fails closed if the plugin subtree differs.

This binds the reviewed plugin content to the integrated source without depending on merge/squash/rebase preserving literal candidate commit identity.

### Qualification and permission boundary

Release execution is split across two GitHub Actions jobs with different authority.

The **prepare** job is read-only and owns all repository-controlled execution. Its permissions are limited to `contents: read`, `actions: read`, and `pull-requests: read`. It performs:

1. merged-PR and exact-head `Foundation Verify` admission;
2. reviewed PR Head → integrated plugin-subtree identity binding;
3. checkout of the exact integrated commit;
4. PHP/tool setup and canonical `bash scripts/verify-foundation.sh` execution, including Composer development tooling;
5. version resolution and release-note validation;
6. reviewed/integrated release-note blob identity binding;
7. canonical ZIP build and ZIP/PHP/SHA-256 verification.

The prepare job then creates one immutable GitHub Actions publication handoff containing the reviewed PR Head, integrated SHA, plugin tree identity, version, tag, release title, reviewed release notes and their bound identities, the canonical ZIP, and its qualified SHA-256.

The **publish** job depends on successful completion of prepare and receives `contents: write`. It does not checkout repository source and does not execute repository scripts, Composer, PHP setup/verification tooling, or rebuild the artifact. It consumes only the immutable handoff. GitHub authentication is exposed only on individual GitHub API/CLI steps that require it; it is not defined workflow-wide or job-wide.

Repository qualification does not prove real WordPress runtime behavior that it did not execute.

### Canonical artifact builder

`scripts/release/build-ksh-kanoon-articles.sh` is the only production ZIP builder. It uses `git archive` directly from the exact integrated commit's plugin subtree rather than packaging the runner working tree.

`scripts/release/verify-ksh-kanoon-articles-zip.sh` validates archive integrity, single installable root, required/forbidden content, packaged version mirrors, PHP syntax and SHA-256. This verifier runs only in the read-only prepare job.

The publication job re-hashes the handed-off ZIP before mutation. It does not re-run repository ZIP-verifier code under write authority. After publication, it downloads the actual Release asset and proves byte-for-byte identity by SHA-256 equality with the already-qualified canonical ZIP; that equality carries the prepare job's structural/version/PHP qualification to the distributed asset without rebuilding or re-executing repository validation code.

### Release notes

Each future release candidate must provide reviewed notes at:

`docs/releases/ksh-kanoon-articles/vX.Y.Z.md`

The required structure is defined by `docs/releases/ksh-kanoon-articles/TEMPLATE.md`. Each mandatory section must contain at least one nonblank content line, unresolved `TODO`/`TBD` markers are rejected, and an unchanged copy of the instructional template is not valid versioned release notes.

Publication uses the exact reviewed notes handed off by the read-only job; it does not synthesize runtime claims. The workflow verifies that the reviewed PR Head and integrated source contain the same release-notes blob before the handoff is created.

### Publication and last-mile verification

Immediately before mutation, the write-authority job rechecks that the exact tag and GitHub Release do not already exist. It then creates the exact lightweight tag, publishes a non-draft/non-prerelease GitHub Release, and uploads the exact pre-qualified ZIP from the immutable handoff.

The same write-authority job downloads that Release asset through GitHub again and compares its SHA-256 with the pre-qualified SHA. It also verifies tag target, Release metadata, GitHub asset digest when available, and release-note identity. It reports `PUBLISHED_AND_VERIFIED` only after those checks pass.

If publication becomes partial (for example the tag exists but Release creation fails), the workflow preserves the successful immutable state, reports the partial condition and fails. It does not move tags, overwrite assets or silently rewrite published history.

### Security and concurrency

- all repository-controlled qualification/build/verification execution remains under read-only job permissions;
- only the isolated publication job receives `contents: write`;
- the write-authority job consumes only an immutable Actions artifact handoff and never rebuilds the production ZIP;
- the workflow uses the repository-scoped ephemeral `GITHUB_TOKEN`, not a permanent PAT, and exposes it only on individual GitHub API/CLI steps that require authentication;
- production publication must be dispatched from `main`;
- the publish job is gated by successful prepare completion;
- a release-unit concurrency group prevents overlapping publication runs;
- tag/release conflicts are checked on the read-only side for early failure and rechecked immediately before mutation on the write-authority side;
- release-critical ambiguity fails closed.

## Consequences

The Owner's normal release action becomes: merge a genuinely qualified release-intended PR, then manually run **Publish KSH Kanoon Articles** with that PR number.

Version bump automation / a separate Prepare Release workflow is deliberately deferred until release frequency or owner workflow demonstrates that the extra layer is useful.

The existing v0.4.0 Release is historical publication evidence and is not mutated by this implementation.
