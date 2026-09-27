# KSH-Web

Official WordPress website project for **Kanoon Farhangi Amoozeshi Shiraz (کانون فرهنگی آموزشی شیراز)**.

The repository contains the site-specific **KSH Kanoon Articles** plugin. It covers qualified acquisition, independent local last-known-good snapshots, explicitly attributed Manual/Cron refresh execution, one approximately-daily WordPress-native refresh schedule, a one-click read-only JSON diagnostic export, and a public local-only article renderer exposed through a standard WordPress shortcode.

## Start here

1. [`docs/MOTHER_PROJECT.md`](docs/MOTHER_PROJECT.md) — canonical project charter, scope, locked decisions, boundaries, and current status.
2. [`docs/design/UI_REFERENCE.md`](docs/design/UI_REFERENCE.md) — responsive UI reference and its authority limits.
3. [`docs/decisions/ADR-001-kanoon-article-list-mirror.md`](docs/decisions/ADR-001-kanoon-article-list-mirror.md) — locked architecture for mirroring Kanoon article-list metadata.
4. [`AGENTS.md`](AGENTS.md) — concise operational entrypoint for AI-assisted engineering.
5. [`docs/REPOSITORY_FOUNDATION.md`](docs/REPOSITORY_FOUNDATION.md) — repository-foundation rationale and intentionally deferred work.

## Kanoon article plugin

Plugin source:

```text
wp-content/plugins/ksh-kanoon-articles/
```

Plugin identity: **KSH Kanoon Articles** (`ksh-kanoon-articles`). Current development version: **0.3.1**.

The wp-admin surface remains under **Tools → آزمون اتصال مقاله‌های کانون** and exposes three bounded actions:

- **Preview/Test Connection** — remote read + parse/validation only; it never mutates local snapshots.
- **Refresh Local Data Now** — remote acquisition through the same qualified candidate producer; only successful validated candidates replace the corresponding local list snapshot and the run is explicitly attributed as `manual`.
- **دانلود گزارش JSON** — authenticated read-only download of current plugin/runtime evidence; it performs no remote acquisition, Refresh, option write, or Cron schedule mutation.

Latest and Weekly Popular snapshots are independent. A failed, empty, malformed, or ambiguous candidate cannot erase a previous valid snapshot for that list. Per-list last-attempt metadata is stored separately from last-known-good data and carries explicit `manual` / `cron` origin plus a bounded run identifier for new executions; legacy unattributed attempts remain readable as `unknown`.

The plugin uses WordPress Options for the small local state and one native daily WP-Cron hook. It preserves only the latest bounded Manual run summary and latest bounded Cron run summary as separate non-autoloaded options; there is no unbounded activity history. A cheap `init` schedule-existence check self-heals the schedule after an in-place plugin upgrade; this check never performs remote acquisition. Deactivation unschedules the hook but deliberately preserves valid snapshots and diagnostic evidence.

Schedule registration and actual Cron execution are intentionally separate facts. `wp_next_scheduled()` proves only that the owned event is registered. `cron_execution_observed=true` appears in the diagnostic JSON only when a persisted `cron` run summary exists from the scheduled callback path.

It does **not** create Posts/CPTs, persist raw remote HTML, mirror article bodies/media, add a Gutenberg block/Elementor widget, or contact `kanoon.ir` while rendering public pages.

Public placement seam:

```text
[ksh_kanoon_articles]
```

The shortcode delegates to a reusable renderer that reads `Snapshot_Store::get_snapshot()` only. It renders the canonical `تازه‌های کانون` section with `تازه‌ها` and `پربازدید هفته`, preserves stored ordering, renders every available validated item, escapes all output, and fails softly when one or both lists are unavailable. `date_context` remains part of validated local snapshot/diagnostic data but is intentionally not displayed in the public module. Presentation is Persian RTL, responsive, scoped below `.ksh-kanoon-articles`, uses no frontend JavaScript, and lets each desktop panel keep its own natural content height.

## Real-host qualification evidence

Three bounded real-host observations now exist.

The Owner first executed the merged v0.1.0 read-only Preview on the real KSH WordPress host:

- Latest: PASS, 20 valid records, HTTP 200;
- Weekly Popular: PASS, 16 valid records, HTTP 200;
- WordPress version visibly observed: 7.1.2.

After merged PR #4, the Owner installed v0.2.1 and downloaded the plugin diagnostic JSON. That artifact observed:

- WordPress 7.1.2 and PHP 8.3.33;
- `event_registered=true`, recurrence `daily`;
- `cron_execution_observed=true`;
- one Cron-origin run with `overall_status=success`;
- Latest candidate/local count 20;
- Weekly Popular candidate/local count 16;
- `observability_incomplete=false` and diagnostic state `CRON_EXECUTION_OBSERVED`.

This proves the observed chain `WP-Cron → acquisition → parser/validation → independent local persistence → diagnostic persistence` for that execution. It does **not** guarantee future Cron firing or future Kanoon DOM/network stability.

The Owner also installed v0.3.0 and rendered `[ksh_kanoon_articles]` on the real KSH site. Desktop and mobile captures showed both lists rendering, the intended two-column desktop arrangement, one-column mobile stacking, and no obvious horizontal overflow in the provided mobile capture. That observed placement/rendering path is therefore no longer wholly `NOT_PROVEN`. The captures also exposed two presentation issues now addressed in v0.3.1: repeated public Latest date metadata and default Grid stretching that made the shorter panel artificially tall. Final visual acceptance remains pending Owner revalidation of the refined version.

## Development verification

Development prerequisites:

- PHP CLI with DOM/libxml support;
- Composer 2;
- Git.

Canonical verification:

```bash
bash scripts/verify-foundation.sh
```

The command runs repository/design integrity checks, PHP syntax validation, WordPress Coding Standards, and deterministic parser/orchestration/persistence/lifecycle/diagnostic/frontend tests. Development dependencies are Composer `require-dev` packages only; the production plugin has no Composer runtime dependency.

Useful focused commands after `composer install`:

```bash
composer cs
composer test
```

Repository tests use bounded stubs for Options/WP-Cron/frontend lifecycle boundaries. They cover Manual/Cron attribution, run correlation, separate latest Manual/Cron summaries, legacy-attempt compatibility, schedule-vs-execution semantics, read-only JSON generation/download, privacy exclusions, local-only frontend rendering/fail-soft behavior, output escaping, ordering/no-truncation, shortcode registration, and scoped responsive CSS contracts. They prove only the exercised deterministic behavior; they do not guarantee future Cron firing, future Kanoon HTML compatibility, or final authentic browser visual acceptance of v0.3.1.

## Operational qualification workflow

The v0.2.1 diagnostic has already established one successful real Cron-origin execution. The JSON download remains the preferred bounded support artifact because it is read-only and does not contact `kanoon.ir`.

After this refinement is merged and v0.3.1 is installed, keep the existing `[ksh_kanoon_articles]` placement, verify both local lists still render without triggering acquisition, capture representative desktop/mobile results, confirm per-item date lines are absent, confirm the shorter desktop panel ends at its natural content height, confirm there is no horizontal overflow, and re-download the diagnostic JSON to verify stored snapshot metadata, scheduler state, and observability remain intact.

## Product direction

- Responsive Persian RTL WordPress website.
- Public homepage and contact/about/service paths.
- Staff/manager-facing access to relevant forms and services.
- Direct student-registration entry path where required.
- A locally rendered `تازه‌ها / پربازدید هفته` module reading only validated local snapshots through `[ksh_kanoon_articles]`.
- No full remote-article mirroring.

## Elementor public homepage candidate

The qualified presentation stack for the current homepage work is **Hello Elementor 3.5.1 + Elementor 4.3.2 + Elementor Pro 4.3.0** on the observed KSH WordPress runtime. The repository still does not own a custom theme and this implementation does not introduce one.

The current WordPress front page remains the existing Plato managers' portal (page ID 62, slug `plato-user-panel`, template `tpl-user-panel.php`). The Owner decision separates that portal from the future public homepage. The observed global Elementor Theme Builder header **Header01** (template ID 341, general header condition) remains global chrome and is not duplicated or modified by the page-body artifact.

The first source-controlled public homepage body candidate is:

`elementor/homepage/ksh-public-homepage-body-v1.json`

It is a bounded Elementor page-template JSON containing a structural public hero, the confirmed manager portal action to `/plato-user-panel/`, and one `[ksh_kanoon_articles]` Shortcode widget. It contains no global header/footer template, site settings, live front-page assignment, Plato implementation, remote article acquisition, external service URLs, or invented contact data.

Canonical verification now includes deterministic homepage-template contract validation through:

`php scripts/validate-elementor-homepage.php`

Actual import into Elementor 4.3.2 and authentic responsive rendering on the KSH host remain `NOT_PROVEN` until the new draft page is imported and inspected. See `elementor/homepage/README.md` for the bounded post-merge procedure.

## License

No license has been declared yet. Do not add or infer a license without an explicit Owner decision.
