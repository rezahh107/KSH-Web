# AGENTS.md

## Purpose

`KSH-Web` is the WordPress website project for Kanoon Farhangi Amoozeshi Shiraz. This file is the concise AI-agent entrypoint; detailed authority belongs in the referenced docs.

## Read order

Before material implementation work, read:

1. `docs/MOTHER_PROJECT.md`
2. the relevant ADR under `docs/decisions/`
3. `docs/design/UI_REFERENCE.md` for UI work
4. `README.md`
5. only then inspect the implementation code relevant to the task

## Authority order

1. Current explicit Owner instruction
2. `docs/MOTHER_PROJECT.md`
3. accepted ADRs under `docs/decisions/`
4. `docs/design/UI_REFERENCE.md` within its stated visual authority
5. implementation code/tests/configuration
6. comments and examples

Do not let generated mockup copy, placeholder text, or visual artifacts override project requirements.

## Current locked product decisions

- WordPress is the target platform.
- The public UI is Persian RTL and responsive.
- Kanoon article integration is metadata/link mirroring only; article bodies and media are not copied.
- The target lists are approximately 20 Latest items and Kanoon's own Weekly Popular ordering.
- Normal visitor requests must not depend on a live request to `kanoon.ir`.
- Refresh may be approximately daily/nightly after qualification.
- Failed refreshes must preserve the previous last-known-good snapshot.
- The selected article acquisition family is a small site-specific WordPress extractor using WordPress Core HTTP APIs and local snapshot storage.
- The real-host read-only Preview/qualification gate must precede writes/scheduling; that gate has now passed for the observed Owner execution.
- Stored `date_context` remains backend/diagnostic metadata and is not part of the current public article-item presentation.

See `docs/decisions/ADR-001-kanoon-article-list-mirror.md` for the complete contract.

## Current implementation

The bounded plugin implementation lives at:

`wp-content/plugins/ksh-kanoon-articles/`

Current plugin capability includes:

- the explicit read-only wp-admin Preview/Test Connection under Tools;
- Owner-observed real-host Preview success for Latest (20 records / HTTP 200) and Weekly Popular (16 records / HTTP 200);
- Owner-observed real-host v0.2.1 Cron execution with `overall_status=success`, Latest local_count=20, Weekly local_count=16, and complete persisted observability on WordPress 7.1.2 / PHP 8.3.33;
- independent non-autoloaded local snapshots and bounded per-list attempt state;
- one canonical refresh service with explicit `manual` / `cron` origin at its production entry points;
- separate non-autoloaded latest Manual/Cron run summaries plus bounded run identity;
- one daily native WP-Cron hook with cheap upgrade-safe schedule self-healing;
- one-click authenticated **دانلود گزارش JSON** support bundle from the existing Tools page;
- deactivation unscheduling without snapshot deletion;
- a public local-only renderer plus the builder/theme-agnostic `[ksh_kanoon_articles]` shortcode, with scoped responsive RTL presentation;
- Owner-observed real-host v0.3.0 shortcode rendering on desktop/mobile; v0.3.1 removes public per-item date metadata and default Grid equal-height stretching while preserving stored metadata and all article items.

Important boundaries:

- opening the admin page, plugin activation, and JSON report generation/download must not contact `kanoon.ir`;
- remote acquisition runs only on explicit Preview, explicit Manual Refresh, or the due scheduled refresh callback;
- `wp_next_scheduled()` / schedule registration never proves the Cron callback executed;
- actual Cron execution is considered observed only from persisted `cron`-origin run evidence written by the scheduled callback path;
- Latest and Weekly Popular remain independently validatable and replaceable;
- repository/stub tests prove exercised renderer, attribution, persistence, scheduling, and export logic but do not guarantee future WP-Cron firing on the KSH host;
- the observed real-host Preview and Cron run qualify those exact runtime executions;
- the observed v0.3.0 desktop/mobile captures qualify shortcode placement/rendering for that execution, while final visual acceptance of the v0.3.1 refinement and future source/runtime behavior remain separate evidence boundaries.

Do not couple public rendering to acquisition/refresh or add builder-specific data logic.

## Public homepage direction

The final public homepage/pages are Owner-built manually in Elementor on the qualified Hello Elementor + Elementor + Elementor Pro stack. Preserve the existing global `Header01`, do not modify the Plato managers' portal, and do not change the live `page_on_front` assignment unless the Owner explicitly authorizes that runtime action.

The merged PR #7 artifact remains at:

`elementor/homepage/ksh-public-homepage-body-v1.json`

It is **historical/technical qualification evidence only**, not the selected final homepage implementation method. Do not instruct future work to import or deploy that JSON as the final homepage. Its deterministic validator remains in the repository only to preserve the historical artifact's contract:

`scripts/validate-elementor-homepage.php`

The current proposed page/destination structure, naming, URL, navigation, and indexing reference is:

`docs/SITE_INFORMATION_ARCHITECTURE.md`

That IA document remains **PROPOSED — OWNER REVIEW REQUIRED** except for decisions explicitly marked Owner-locked, including `/reg/`.

## Design authority

Use `docs/design/assets/homepage-responsive-reference.webp` together with `docs/design/UI_REFERENCE.md`.

The image is a composition/reference artifact, not textual-content authority. Preserve the responsive structure, visual language, hierarchy, and placement intent; do not reproduce obvious generated-image text errors as product copy.

## Verification

Canonical repository verification:

```bash
bash scripts/verify-foundation.sh
```

It covers foundation/design integrity, deterministic validation of the retained historical Elementor homepage artifact, plus plugin source with PHP syntax, WPCS, and deterministic parser/orchestration/persistence/lifecycle/diagnostic-export/frontend tests. The Owner's downloaded v0.2.1 diagnostic has already proven one real Cron-origin execution; repository tests still cannot guarantee future Cron runs or prove final manually built page behavior. `composer.json` is development tooling only; do not introduce a production Composer runtime dependency unless a future product capability genuinely requires one.

Focused checks after dependency installation:

```bash
composer cs
composer test
```

Do not treat static/unit/fixture/stub success as proof of future WP-Cron execution, future live-source compatibility, or authentic real-page visual fidelity.

## Change boundaries

- Prefer the smallest sufficient change.
- Do not reopen locked architecture during ordinary implementation unless direct evidence falsifies it.
- Do not perform unrelated refactors or governance expansion.
- Never commit real secrets, credentials, `wp-config.php`, or live environment files.
- Do not add a license without Owner authorization.
- Do not treat CI/configuration presence as proof of runtime behavior it did not execute.
- Production deployment, destructive migration, and live-data mutation require explicit authorization.

## Definition of Done for repository changes

A change is complete only when:

- it preserves current project authority and stated scope;
- relevant docs/ADRs are updated when the contract or current evidence changes;
- the canonical verification command passes for the exact resulting Head;
- any unexecuted runtime/production claims remain explicitly `NOT_PROVEN` rather than being inferred.
