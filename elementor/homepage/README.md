# Historical Elementor public homepage body artifact

This directory retains the source-controlled public-homepage **body** artifact merged in PR #7 as historical/technical qualification evidence.

It is **not the selected final homepage implementation method**. The Owner will manually build the final public pages in Elementor. Do not use the procedure below as a current deployment instruction unless the Owner explicitly asks to reproduce or inspect the historical qualification path.

For current product/page structure, read `docs/MOTHER_PROJECT.md` first and then `docs/SITE_INFORMATION_ARCHITECTURE.md`. The IA remains **PROPOSED — OWNER REVIEW REQUIRED** except for decisions explicitly marked Owner-locked.

## Retained artifact

`ksh-public-homepage-body-v1.json`

Format: Elementor page-template JSON using the documented page data shape (`type: page`, data `version: 0.4`, `page_settings`, `content`). The artifact contains only page-body composition; it does not contain site settings, Theme Builder templates, WordPress users/content export, plugin state, or live-site configuration.

At its qualification checkpoint, the artifact represented the largest then-authorized public subset:

- structural public hero for **کانون فرهنگی آموزشی شیراز / نمایندگی شیراز**;
- one expandable quick-access/service-card area containing only the confirmed manager portal destination `/plato-user-panel/`;
- one Elementor Shortcode widget containing `[ksh_kanoon_articles]` exactly once.

Those constraints describe the retained artifact; they do not limit the final manually built homepage after the Owner approves the current Information Architecture.

## Historical reproduction / evidence procedure only

If an explicit future task requires reproducing the PR #7 qualification path, preserve the original boundaries:

1. Keep the existing Plato page, Header01, footer state, and WordPress front-page assignment unchanged.
2. Import `ksh-public-homepage-body-v1.json` only into a non-production draft/evidence page.
3. Use a page layout that preserves Theme Builder chrome; do not use Elementor Canvas if it removes Header01.
4. Confirm Header01 remains visible and no duplicate header/footer is introduced by the body artifact.
5. Confirm the retained manager action reaches `/plato-user-panel/` and Plato itself remains unchanged.
6. Confirm `[ksh_kanoon_articles]` renders from the accepted plugin/local-snapshot boundary.
7. If visual evidence is required, inspect representative desktop/tablet/mobile widths without changing the live `page_on_front` assignment.

This reproduction path must not be interpreted as authorization to publish the artifact as the final homepage.

## Verification boundary

`scripts/validate-elementor-homepage.php` remains in canonical repository verification to guard the retained JSON artifact against silent structural drift.

That deterministic validation proves only the artifact's repository contract. It does **not** prove or prescribe the final manually built Elementor homepage, change live WordPress state, validate current IA decisions, or establish authentic browser behavior for pages the Owner has not yet built.
