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
- **publish** has the bounded repository write permission required for Tag/Release creation. It does not checkout source, run Composer/PHP verification, execute repository release scripts, or rebuild the ZIP. It consumes only the qualified handoff, rechecks tag/Release conflicts, publishes that exact ZIP, downloads the published Release asset, and requires its SHA-256 to match the pre-qualified artifact before reporting `PUBLISHED_AND_VERIFIED`.

GitHub authentication is step-scoped to the API/CLI operations that require it rather than exposed job-wide.

Do not manually calculate the tag, build a production ZIP, move an existing tag, overwrite a published asset or substitute GitHub's repository source ZIP for the plugin release asset.

The already-published `ksh-kanoon-articles-v0.4.0` release predates this automation and is historical publication evidence; this Release System does not rewrite it.

Architecture and failure/recovery semantics are defined in `docs/decisions/ADR-002-ksh-kanoon-articles-release-system.md`.
