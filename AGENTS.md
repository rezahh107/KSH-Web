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
- `تازه‌ها / Latest` means the semantic `تازه‌ها` tab/list on `https://www.kanoon.ir/`; `/Article/Days` is not a fallback source for that public meaning.
- `پربازدید هفته / Weekly Popular` remains Kanoon's homepage semantic list and preserves Kanoon's own ordering.
- Local snapshots may preserve the complete valid owned lists; public rendering shows at most the first 15 links per list.
- Normal visitor requests must not depend on a live request to `kanoon.ir`.
- Refresh may be approximately daily/nightly after qualification.
- Failed refreshes must preserve the previous last-known-good snapshot.
- The selected article acquisition family is a small site-specific WordPress extractor using WordPress Core HTTP APIs and local snapshot storage.
- The historical real-host read-only Preview/qualification gate passed for the then-current v0.1.0 source/parser path; the later homepage-Latest semantic change needs its own current-source/runtime evidence and must not inherit that proof automatically.
- Stored `date_context` remains backend/diagnostic metadata and is not part of the current public article-item presentation; homepage Latest may store an empty date when no reliable bounded article date exists.
- Font-family/asset delivery is not owned by KSH Kanoon Articles. The module inherits site typography; the exact Owner companion `rezahh107/Vazir` owns self-hosted `Vazirmatn` delivery when enabled.

See `docs/decisions/ADR-001-kanoon-article-list-mirror.md` for the complete contract.

## Current implementation

The bounded plugin implementation lives at:

`wp-content/plugins/ksh-kanoon-articles/`

Current plugin capability includes:

- the explicit read-only wp-admin Preview/Test Connection under Tools;
- historical Owner-observed real-host Preview success for the v0.1.0 source/parser path (Latest 20 records / HTTP 200 and Weekly Popular 16 records / HTTP 200);
- historical Owner-observed real-host v0.2.1 Cron execution with `overall_status=success`, Latest local_count=20, Weekly local_count=16, and complete persisted observability on WordPress 7.1.2 / PHP 8.3.33;
- independent non-autoloaded local snapshots and bounded per-list attempt state;
- one canonical refresh service with explicit `manual` / `cron` origin at its production entry points;
- separate non-autoloaded latest Manual/Cron run summaries plus bounded run identity;
- one daily native WP-Cron hook with cheap upgrade-safe schedule self-healing;
- one-click authenticated **دانلود گزارش JSON** support bundle from the existing Tools page;
- deactivation unscheduling without snapshot deletion;
- a public local-only renderer plus the builder/theme-agnostic `[ksh_kanoon_articles]` shortcode, with scoped responsive RTL presentation;
- Owner-observed real-host v0.3.0 shortcode rendering on desktop/mobile; v0.3.1 removed public per-item date metadata and default Grid equal-height stretching;
- the v0.4.0 refinement changes Latest acquisition to the homepage semantic tab/target, keeps complete valid snapshots while capping public output at 15 per list, and tightens component typography while inheriting the site's font family. Real-host acceptance of v0.4.0 remains separate evidence.

Important boundaries:

- opening the admin page, plugin activation, public rendering, and JSON report generation/download must not contact `kanoon.ir`;
- remote acquisition runs only on explicit Preview, explicit Manual Refresh, or the due scheduled refresh callback;
- `wp_next_scheduled()` / schedule registration never proves the Cron callback executed;
- actual Cron execution is considered observed only from persisted `cron`-origin run evidence written by the scheduled callback path;
- Latest and Weekly Popular remain independently validatable and replaceable even though both currently acquire the homepage URL;
- the 15-item public limit is presentation-only and must not truncate otherwise valid persisted list data;
- repository/stub tests prove exercised renderer, attribution, persistence, scheduling, and export logic but do not guarantee future WP-Cron firing on the KSH host;
- historical Preview/Cron observations qualify only the exact runtime/source behavior they executed;
- the observed v0.3.0 desktop/mobile captures qualify shortcode placement/rendering for that execution; v0.4.0 real-host/browser acceptance remains pending Owner installation/testing.

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

It covers foundation/design integrity, deterministic validation of the retained historical Elementor homepage artifact, plus plugin source with PHP syntax, WPCS, and deterministic parser/orchestration/persistence/lifecycle/diagnostic-export/frontend tests. The historical v0.2.1 diagnostic proved one real Cron-origin execution; repository tests still cannot guarantee future Cron runs, future Kanoon DOM compatibility, or real-page behavior. `composer.json` is development tooling only; do not introduce a production Composer runtime dependency unless a future product capability genuinely requires one.

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
