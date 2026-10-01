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

`scripts/release/build-ksh-kanoon-articles.sh` is the only production ZIP builder. It resolves the supplied source ref once to an immutable commit, then archives the exact plugin subtree from that commit rather than packaging the runner working tree.

Archive metadata is part of production artifact identity. A subtree expression such as `<commit>:wp-content/plugins/ksh-kanoon-articles` resolves to a Git tree; without an explicit archive time, `git archive` assigns build-time timestamps to ZIP entries. The canonical builder therefore derives one timestamp from the immutable source commit's committer epoch, invokes `git archive --mtime=@<source-epoch>` with `TZ=UTC`, and applies that same deterministic timestamp to every archive entry. For the same immutable source commit, plugin subtree, version and builder/configuration, repeated builds must be byte-identical and produce the same SHA-256 regardless of execution time or caller timezone.

`scripts/release/verify-ksh-kanoon-articles-zip.sh` validates archive integrity, single installable root, required/forbidden content, packaged version mirrors, exact Author metadata, PHP syntax and SHA-256. The deterministic release regression additionally builds the same immutable source more than once outside the ZIP timestamp-resolution window, verifies byte/SHA equality and commit-derived entry timestamps, and proves that a real packaged-source change changes artifact identity while the package verifier continues to pass. These checks run only in the read-only qualification boundary.

The publication job re-hashes the handed-off ZIP before mutation. It does not re-run repository ZIP-verifier code under write authority. After publication, it downloads the actual Release asset and proves byte-for-byte identity by SHA-256 equality with the already-qualified canonical ZIP; that equality carries the prepare job's structural/version/PHP qualification to the distributed asset without rebuilding or re-executing repository validation code.

### Release notes

Each future release candidate must provide reviewed notes at:

`docs/releases/ksh-kanoon-articles/vX.Y.Z.md`

The required structure is defined by `docs/releases/ksh-kanoon-articles/TEMPLATE.md`. Each mandatory section must contain at least one nonblank content line, unresolved `TODO`/`TBD` markers are rejected, and an unchanged copy of the instructional template is not valid versioned release notes.

Publication uses the exact reviewed notes handed off by the read-only job; it does not synthesize runtime claims. The workflow verifies that the reviewed PR Head and integrated source contain the same release-notes blob before the handoff is created.

### Publication state and resumable recovery

Immediately before mutation, the write-authority job determines the current GitHub publication state from authenticated API responses and the pre-qualified handoff. A confirmed HTTP `404` means the requested tag/Release is absent. Authentication, transport, rate-limit, server failure, malformed response, or any other inability to determine state is not treated as absence and fails closed.

The admissible states are:

- `ABSENT` — neither tag nor Release exists. Create the exact lightweight tag and then the Release with the exact pre-qualified ZIP.
- `TAG_ONLY_MATCHING` — the expected tag exists, is a lightweight commit tag, and targets exactly the handoff `integrated_sha`; the Release is absent. Reuse the immutable tag and create only the Release plus asset.
- `RELEASE_MATCHING_ASSET_MISSING` — the expected tag and Release exist; tag target, Release name, draft/prerelease state and reviewed notes all match the handoff; there are no unexpected assets; and the expected asset is absent. Upload only the exact handed-off ZIP without clobber/overwrite semantics.
- `RELEASE_MATCHING_STARTER_ASSET` — the surrounding tag/Release/notes identity matches the handoff, exactly one expected-name asset exists, no unexpected assets exist, and GitHub reports that asset as `state=starter` with `size=0`. This is the narrow failed-upload artifact documented by GitHub for an upstream Release Asset upload failure. Delete only that exact starter asset by its GitHub `asset_id`, require the deletion API call to succeed, then upload the exact pre-qualified handoff ZIP. No normal uploaded asset is eligible for this deletion path.
- `PUBLISHED_MATCHING` — the tag, Release metadata/notes and expected asset already match the handoff, including downloaded asset SHA-256. Perform no mutation and continue through the normal last-mile verification so the run can converge to `PUBLISHED_AND_VERIFIED`.

GitHub's Release Asset API documents that an upstream upload failure may return HTTP `502` and leave an empty asset with `state=starter`, and explicitly states that such a starter asset can be safely deleted. The Release Asset delete endpoint is bound to the asset's numeric `asset_id`; the workflow uses that supported boundary rather than any generic replacement operation.

Every other state fails closed before mutation. Examples include a wrong tag target, a Release existing without its expected tag, unexpected Release title/draft/prerelease state, release-note mismatch, multiple or unexpected assets, a `starter` asset that is not empty, an unsupported asset state, or an ordinary `uploaded` expected-name asset whose GitHub digest/downloaded SHA-256 differs from the qualified artifact.

An existing tag is never moved or rewritten. A normal uploaded asset is never overwritten, deleted or replaced. The only asset deletion admitted is the exact numeric ID of the uniquely qualified empty `starter` asset described above. If deletion fails or its result is uncertain, upload is not attempted. If starter deletion succeeds but the subsequent upload fails, the run fails without rollback; the next canonical run re-classifies the resulting remote state and may safely continue from `RELEASE_MATCHING_ASSET_MISSING` or another newly observed qualified state.

### Last-mile verification

After normal publication, recovery publication, starter recovery, or a no-op `PUBLISHED_MATCHING` convergence, the write-authority job downloads the Release asset through GitHub again and compares its SHA-256 with the pre-qualified SHA. It also verifies tag target, Release metadata, GitHub asset digest when available, and release-note identity. It reports `PUBLISHED_AND_VERIFIED` only after those checks pass.

### Security and concurrency

- all repository-controlled qualification/build/verification execution remains under read-only job permissions;
- only the isolated publication job receives `contents: write`;
- the write-authority job consumes only an immutable Actions artifact handoff and never rebuilds the production ZIP;
- the workflow uses the repository-scoped ephemeral `GITHUB_TOKEN`, not a permanent PAT, and exposes it only on individual GitHub API/CLI steps that require authentication;
- production publication must be dispatched from `main`;
- the publish job is gated by successful prepare completion;
- a release-unit concurrency group prevents overlapping publication runs;
- remote publication state is re-classified immediately before mutation and ambiguity fails closed;
- no recovery path moves a tag or uses general asset clobber/overwrite semantics;
- only a uniquely qualified empty GitHub `starter` asset may be deleted, by its exact asset ID.

## Consequences

The Owner's normal release action becomes: merge a genuinely qualified release-intended PR, then manually run **Publish KSH Kanoon Articles** with that PR number. If a transient publication failure leaves a matching partial state, re-running the same canonical workflow with the same merged PR number safely resumes from that state.

Version bump automation / a separate Prepare Release workflow is deliberately deferred until release frequency or owner workflow demonstrates that the extra layer is useful.

The existing v0.4.0 Release is historical publication evidence and is not mutated by this implementation.

The already published `ksh-kanoon-articles-v0.4.1` Release is also immutable historical evidence. Its verified asset SHA-256 remains `a68b0031f38cd05423230cb08585fb7525267729fd1df2b039c9f93a3fc5fac9`; it was built before deterministic archive metadata was introduced and must not be rewritten, replaced, retagged or normalized. A fresh dispatch for that pre-fix release remains a documented historical exception rather than receiving a legacy SHA bypass: the old integrated source contains the old builder, and a newly deterministic build from the same source would intentionally have different archive metadata from the already published pre-fix bytes. Future artifacts built under the repaired Release System must satisfy byte-identical rebuild semantics; ordinary SHA mismatch remains fail-closed.
