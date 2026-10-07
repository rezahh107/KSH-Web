# AGENTS.md

## Purpose

`KSH-Web` is the WordPress website project for Kanoon Farhangi Amoozeshi Shiraz. This file is the concise AI-agent entrypoint; detailed authority belongs in the referenced docs.

## Read order

Before material implementation work, read:

1. `docs/MOTHER_PROJECT.md`
2. the relevant ADR under `docs/decisions/`
3. `docs/design/PAGE05_PRODUCTION_REFERENCE.md` for Page 05 implementation/qualification
4. `docs/design/UI_REFERENCE.md` for shared/historical UI-reference boundaries
5. `README.md`
6. only then inspect the implementation code/runtime relevant to the task

## Authority order

1. Current explicit Owner instruction
2. `docs/MOTHER_PROJECT.md`
3. accepted ADRs under `docs/decisions/`
4. `docs/design/UI_REFERENCE.md` within its stated visual authority
5. implementation code/tests/configuration
6. comments and examples

Do not let generated mockup copy, placeholder text, or visual artifacts override project requirements.

## Current locked product decisions

- The KSH Nine-Page Guidance Design System v1.0.1 is complete and locked; Design System completion is not page-runtime completion.
- Page 05 Master v1.1 and Hero v1.0 are the current locked Page 05 implementation authority.
- `PAGE_05_ELEMENTOR_PRODUCTION_REFERENCE` is the immediate milestone.
- `ELEMENTOR_RUNTIME_STATUS = NOT_PROVEN` until authentic native Elementor/browser qualification occurs.
- KSH Kanoon Articles v0.4.1 current-contract real-host qualification remains unresolved but is deferred from immediate priority.
- Pages 01–04 and 06–09 remain unimplemented/unproven unless separately evidenced.
- WordPress is the target platform.
- The public UI is Persian RTL and responsive.
- Kanoon article integration is metadata/link mirroring only; article bodies and media are not copied.
- `تازه‌ها / Latest` means the semantic `تازه‌ها` tab/list on `https://www.kanoon.ir/`; `/Article/Days` is not a fallback source for that public meaning.
- `پربازدید هفته / Weekly Popular` remains Kanoon's homepage semantic list and preserves Kanoon's own ordering.
- Local snapshots may preserve the complete valid owned lists; public rendering shows at most the first 15 links per list.
- Normal visitor requests must not depend on a live request to `kanoon.ir`.
- Refresh may be approximately daily/nightly only after the exact current acquisition contract is qualified.
- Failed refreshes must preserve the previous last-known-good snapshot.
- The selected article acquisition family is a small site-specific WordPress extractor using WordPress Core HTTP APIs and local snapshot storage.
- Acquisition qualification is a runtime admission rule, not documentation-only sequencing. The exact current source/parser contract has an explicit identity independent from the plugin version; historical qualification from another identity must not authorize it.
- Ordinary Preview remains read-only. A separate protected Owner action may record successful qualification only after the exact current Preview checks pass for both lists.
- Stored `date_context` remains backend/diagnostic metadata and is not part of the current public article-item presentation; homepage Latest may store an empty date when no reliable bounded article date exists.
- Font-family/asset delivery is not owned by KSH Kanoon Articles. The module inherits site typography; the exact Owner companion `rezahh107/Vazir` owns self-hosted `Vazirmatn` delivery when enabled.

See `docs/decisions/ADR-001-kanoon-article-list-mirror.md` for the complete contract.

## Current implementation

The bounded plugin implementation lives at:

`wp-content/plugins/ksh-kanoon-articles/`

Current plugin capability includes:

- the explicit read-only wp-admin Preview/Test Connection under Tools;
- a separate capability/nonce-protected Tools action that executes the exact current Preview checks and records one bounded qualification state for the explicit acquisition-contract identity only after both lists succeed;
- historical Owner-observed real-host Preview success for the v0.1.0 source/parser path (Latest 20 records / HTTP 200 and Weekly Popular 16 records / HTTP 200);
- historical Owner-observed real-host v0.2.1 Cron execution with `overall_status=success`, Latest local_count=20, Weekly local_count=16, and complete persisted observability on WordPress 7.1.2 / PHP 8.3.33;
- independent non-autoloaded local snapshots and bounded per-list attempt state;
- one canonical refresh service with explicit `manual` / `cron` origin at its production entry points and fail-closed current-contract qualification admission before acquisition or mutation;
- separate non-autoloaded latest Manual/Cron run summaries plus bounded run identity;
- one daily native WP-Cron hook that exists only for a qualified current contract; upgrade-safe schedule self-healing clears a stale previous-contract event while unqualified;
- an independent Cron callback admission guard so an already-registered stale event cannot bypass current-contract qualification;
- one-click authenticated **دانلود گزارش JSON** support bundle from the existing Tools page;
- deactivation unscheduling without snapshot deletion;
- a public local-only renderer plus the builder/theme-agnostic `[ksh_kanoon_articles]` shortcode, with scoped responsive RTL presentation;
- Owner-observed real-host v0.3.0 shortcode rendering on desktop/mobile; v0.3.1 removed public per-item date metadata and default Grid equal-height stretching;
- the v0.4.0 refinement changes Latest acquisition to the homepage semantic tab/target, keeps complete valid snapshots while capping public output at 15 per list, tightens component typography while inheriting the site's font family, and requires new exact-contract real-host qualification before Manual/Cron mutation is admitted.

Important boundaries:

- opening either admin page, plugin activation, public rendering, and JSON report generation/download must not contact `kanoon.ir`;
- remote acquisition runs only on explicit read-only Preview, the explicit Owner qualification action, a qualified explicit Manual Refresh, or a qualified due scheduled refresh callback;
- while the current acquisition contract is unqualified, Manual/Cron refresh is blocked before acquisition and before snapshot/attempt/run-summary writes; existing LKG snapshots and public local rendering remain available;
- ordinary Preview never silently qualifies the contract;
- `wp_next_scheduled()` / schedule registration never proves the Cron callback executed;
- actual Cron execution is considered observed only from persisted `cron`-origin run evidence written by the qualified scheduled callback path;
- Latest and Weekly Popular remain independently validatable and replaceable even though both currently acquire the homepage URL;
- the 15-item public limit is presentation-only and must not truncate otherwise valid persisted list data;
- repository/stub tests prove exercised renderer, attribution, qualification admission, persistence, scheduling, and export logic but do not qualify the real KSH host;
- historical Preview/Cron observations qualify only the exact runtime/source behavior they executed;
- the observed v0.3.0 desktop/mobile captures qualify shortcode placement/rendering for that execution; v0.4.0 real-host acquisition-contract qualification remains `NOT_PROVEN` until the Owner executes the exact repaired build on KSH and the current-contract qualification action succeeds.

Do not couple public rendering to acquisition/refresh or add builder-specific data logic.

## Public homepage direction

Public pages are implemented with the authentic KSH Elementor stack, preserving the existing global `Header01`. The historical PR #7 homepage JSON remains evidence only and is not a generic page-production mechanism.

For the current milestone, authorized Executor work may construct Page 05 natively in Elementor from the locked Page 05 reference. Do not recreate the historical homepage-import workflow, introduce a parallel renderer, modify the Plato managers' portal, or change the live `page_on_front` assignment.

The current proposed page/destination structure remains `docs/SITE_INFORMATION_ARCHITECTURE.md`. It is **PROPOSED — OWNER REVIEW REQUIRED** except for explicit Owner locks such as `/reg/`. If authentic runtime inspection does not reveal an authoritative Page 05 path, use a safe draft/staging/template surface rather than inventing a permanent public URL.

## Design authority

Current whole-site design authority:

- `KSH_NINE_PAGE_DESIGN_SYSTEM_v1.0.1_COMPLETE.zip` — `DESIGN_SYSTEM_STATUS = COMPLETE`.

Current Page 05 implementation authority:

- `docs/design/PAGE05_PRODUCTION_REFERENCE.md`;
- `KSH_PAGE05_MASTER_REFERENCE_v1.1`;
- `KSH_PAGE05_HERO_REFERENCE_v1.0`.

The large canonical Page 05 binary masters remain in the established Owner Drive authority store; the repository keeps their exact names, dimensions, hashes, provenance/status, and runtime claim boundary. Do not create a second asset-management subsystem merely to duplicate those binaries.

The repository-owned `docs/design/assets/homepage-responsive-reference.webp` plus `docs/design/UI_REFERENCE.md` remain historical homepage/article-composition evidence. They do not override Page 05-specific authority.

Static masters never prove Elementor/browser/accessibility behavior. Preserve `ELEMENTOR_RUNTIME_STATUS = NOT_PROVEN` until the authentic runtime path has actually been exercised.

## Verification

Canonical repository verification:

```bash
bash scripts/verify-foundation.sh
```

It covers foundation/design integrity, deterministic validation of the retained historical Elementor homepage artifact, plus plugin source with PHP syntax, WPCS, and deterministic parser/orchestration/persistence/lifecycle/qualification/diagnostic-export/frontend tests. The historical v0.2.1 diagnostic proved one real Cron-origin execution; repository tests still cannot guarantee future Cron runs, future Kanoon DOM compatibility, or real-page behavior. `composer.json` is development tooling only; do not introduce a production Composer runtime dependency unless a future product capability genuinely requires one.

Focused checks after dependency installation:

```bash
composer cs
composer test
```

Do not treat static/unit/fixture/stub success as proof of current-contract real-host qualification, future WP-Cron execution, future live-source compatibility, or authentic real-page visual fidelity.

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
