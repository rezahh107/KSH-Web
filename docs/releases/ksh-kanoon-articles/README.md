# KSH Kanoon Articles — Release Runbook

The plugin is the Release Unit. Normal feature/site work does not publish a release automatically.

## Prepare a release-intended PR

1. Update both active plugin version declarations to the same `X.Y.Z` value:
   - `wp-content/plugins/ksh-kanoon-articles/ksh-kanoon-articles.php` → plugin header `Version`;
   - `wp-content/plugins/ksh-kanoon-articles/includes/class-plugin.php` → `Plugin::VERSION`.
2. Add reviewed release notes at `docs/releases/ksh-kanoon-articles/vX.Y.Z.md` using `TEMPLATE.md` as instructions, not as copy-ready release content.
3. Populate every mandatory section with substantive content. Keep runtime claims evidence-bounded and use `NOT_PROVEN` for material behavior not directly exercised for that release.
4. Let `Foundation Verify` pass on the exact PR Head and complete normal review before merge.

## Publish after merge

1. Open GitHub Actions → **Publish KSH Kanoon Articles**.
2. Run it from `main`.
3. Enter only the merged release-intended PR number.

The workflow uses two permission-isolated jobs:

- **prepare** is read-only. It resolves and binds the reviewed PR Head to the exact integrated plugin tree, re-runs canonical verification, validates reviewed release notes, builds the canonical ZIP from the immutable Git object, validates ZIP/PHP/version structure, computes SHA-256, and uploads one immutable publication handoff.
- **publish** has the bounded repository write permission required for Tag/Release creation. It does not checkout source, run Composer/PHP verification, execute repository release scripts, or rebuild the ZIP. It consumes only the qualified handoff, classifies current GitHub publication state, performs only the missing safe mutation, downloads the published Release asset, and requires its SHA-256 to match the pre-qualified artifact before reporting `PUBLISHED_AND_VERIFIED`.

The published asset is not structurally re-verified by repository code under write authority. Byte-for-byte SHA-256 equality with the already-qualified handoff is the bridge carrying the read-only job's ZIP/version/PHP qualification to the distributed asset.

GitHub authentication is step-scoped to the API/CLI operations that require it rather than exposed job-wide.

## Safe re-run after a partial publication

Re-run the same **Publish KSH Kanoon Articles** workflow from `main` with the same merged release-intended PR number. The workflow admits only exact matching recovery states:

- `ABSENT` — create tag + Release + asset normally.
- `TAG_ONLY_MATCHING` — keep the existing exact tag and create only the Release + asset.
- `RELEASE_MATCHING_ASSET_MISSING` — keep the existing exact tag/Release and upload only the missing exact asset.
- `RELEASE_MATCHING_STARTER_ASSET` — the exact tag/Release/notes match, exactly one expected-name asset exists, no unexpected assets exist, and GitHub reports that asset as `state=starter` with `size=0`. Delete only that exact starter asset by its GitHub asset ID, require deletion success, then upload the exact pre-qualified artifact.
- `PUBLISHED_MATCHING` — perform no mutation and re-run last-mile verification to converge successfully.

GitHub documents one narrow failed-upload condition where an upstream Release Asset upload failure can leave an empty asset with `state=starter`; GitHub explicitly states that this starter asset can be safely deleted. This is the only existing-asset deletion the canonical workflow permits.

A genuine uploaded expected-name asset with wrong digest/content is **not** recoverable: it fails closed and is never deleted or overwritten. The same fail-closed rule applies to wrong tag targets, unexpected Release metadata, release-note mismatch, unknown asset states, multiple expected-name assets, or any unexpected additional asset.

GitHub API `404` is the only absence signal. Authentication, transport, rate-limit or server failures are treated as indeterminate and do not authorize mutation. If starter deletion fails or is uncertain, upload is not attempted. If deletion succeeds but upload then fails, do not manually patch the Release; simply re-run the canonical workflow so it can re-classify the remote state.

Never move or rewrite an existing tag. Never use generic asset clobber/overwrite behavior. Never delete a normal uploaded asset. Do not manually repair a partial state if the canonical workflow can safely resume it.

Do not manually calculate the tag, build a production ZIP, move an existing tag, overwrite a published asset or substitute GitHub's repository source ZIP for the plugin release asset.

The already-published `ksh-kanoon-articles-v0.4.0` release predates this automation and is historical publication evidence; this Release System does not rewrite it.

Architecture and failure/recovery semantics are defined in `docs/decisions/ADR-002-ksh-kanoon-articles-release-system.md`.
