# KSH Kanoon Articles — Release Runbook

The plugin is the Release Unit. Normal feature/site work does not publish a release automatically.

## Prepare a release-intended PR

1. Update both active plugin version declarations to the same `X.Y.Z` value:
   - `wp-content/plugins/ksh-kanoon-articles/ksh-kanoon-articles.php` → plugin header `Version`;
   - `wp-content/plugins/ksh-kanoon-articles/includes/class-plugin.php` → `Plugin::VERSION`.
2. Add reviewed release notes at `docs/releases/ksh-kanoon-articles/vX.Y.Z.md` using `TEMPLATE.md`.
3. Keep runtime claims evidence-bounded. Use `NOT_PROVEN` for material behavior not directly exercised for that release.
4. Let `Foundation Verify` pass on the exact PR Head and complete normal review before merge.

## Publish after merge

1. Open GitHub Actions → **Publish KSH Kanoon Articles**.
2. Run it from `main`.
3. Enter only the merged release-intended PR number.

The workflow resolves the reviewed PR Head and exact integrated commit, proves plugin-subtree and release-notes identity, re-runs canonical verification, builds the ZIP from the immutable Git object, validates it, creates the exact tag and GitHub Release, uploads the asset, downloads that published asset again and verifies its SHA-256/structure/version before reporting `PUBLISHED_AND_VERIFIED`.

Do not manually calculate the tag, build a production ZIP, move an existing tag, overwrite a published asset or substitute GitHub's repository source ZIP for the plugin release asset.

The already-published `ksh-kanoon-articles-v0.4.0` release predates this automation and is historical publication evidence; this Release System does not rewrite it.

Architecture and failure/recovery semantics are defined in `docs/decisions/ADR-002-ksh-kanoon-articles-release-system.md`.
