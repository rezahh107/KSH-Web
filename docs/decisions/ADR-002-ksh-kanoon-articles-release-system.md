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

### Qualification

Before publication the workflow requires:

1. the PR is merged into `main`;
2. an exact-head successful `Foundation Verify` pull-request run exists for the reviewed PR Head;
3. reviewed and integrated plugin subtrees are identical;
4. canonical `bash scripts/verify-foundation.sh` succeeds again after checkout of the exact integrated commit;
5. release version, notes and artifact contracts pass;
6. no conflicting tag or GitHub Release exists.

Repository qualification does not prove real WordPress runtime behavior that it did not execute.

### Canonical artifact builder

`scripts/release/build-ksh-kanoon-articles.sh` is the only production ZIP builder. It uses `git archive` directly from the exact integrated commit's plugin subtree rather than packaging the runner working tree.

`scripts/release/verify-ksh-kanoon-articles-zip.sh` validates archive integrity, single installable root, required/forbidden content, packaged version mirrors, PHP syntax and SHA-256.

### Release notes

Each future release candidate must provide reviewed notes at:

`docs/releases/ksh-kanoon-articles/vX.Y.Z.md`

The required structure is defined by `docs/releases/ksh-kanoon-articles/TEMPLATE.md`. Publication uses that exact reviewed file; it does not synthesize runtime claims. The workflow also verifies that the reviewed PR Head and integrated source contain the same release-notes blob.

### Publication and last-mile verification

The `Publish KSH Kanoon Articles` workflow creates an exact lightweight tag, publishes a non-draft/non-prerelease GitHub Release, uploads the canonical ZIP, downloads that Release asset through GitHub again, compares SHA-256, re-runs artifact validation, verifies tag target, release metadata and release-notes identity, and reports `PUBLISHED_AND_VERIFIED` only after those checks pass.

If publication becomes partial (for example the tag exists but Release creation fails), the workflow preserves the successful immutable state, reports the partial condition and fails. It does not move tags, overwrite assets or silently rewrite published history.

### Security and concurrency

- ordinary repository verification remains read-only;
- publication alone receives the minimum GitHub write permission required for Release/tag creation;
- the workflow uses the repository-scoped ephemeral `GITHUB_TOKEN`, not a permanent PAT;
- production publication must be dispatched from `main`;
- a release-unit concurrency group prevents overlapping publication jobs;
- tag/release conflicts are checked immediately before publication and fail closed.

## Consequences

The Owner's normal release action becomes: merge a genuinely qualified release-intended PR, then manually run **Publish KSH Kanoon Articles** with that PR number.

Version bump automation / a separate Prepare Release workflow is deliberately deferred until release frequency or owner workflow demonstrates that the extra layer is useful.

The existing v0.4.0 Release is historical publication evidence and is not mutated by this implementation.
