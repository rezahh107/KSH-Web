# KSH-Web

Official WordPress website project for **Kanoon Farhangi Amoozeshi Shiraz (کانون فرهنگی آموزشی شیراز)**.

The repository contains the site-specific **KSH Kanoon Articles** plugin. It covers qualified acquisition, independent local last-known-good snapshots, explicitly attributed Manual/Cron refresh execution, one approximately-daily WordPress-native refresh schedule after qualification, a one-click read-only JSON diagnostic export, and a public local-only article renderer exposed through a standard WordPress shortcode.

## Start here

1. [`docs/MOTHER_PROJECT.md`](docs/MOTHER_PROJECT.md) — canonical project charter, scope, locked decisions, boundaries, and current status.
2. [`docs/SITE_INFORMATION_ARCHITECTURE.md`](docs/SITE_INFORMATION_ARCHITECTURE.md) — proposed Owner-review reference for page/destination structure, names, URLs, navigation, and indexing intent; only explicitly marked Owner locks are final.
3. [`docs/design/UI_REFERENCE.md`](docs/design/UI_REFERENCE.md) — responsive UI reference and its authority limits.
4. [`docs/decisions/ADR-001-kanoon-article-list-mirror.md`](docs/decisions/ADR-001-kanoon-article-list-mirror.md) — locked architecture for mirroring Kanoon article-list metadata.
5. [`AGENTS.md`](AGENTS.md) — concise operational entrypoint for AI-assisted engineering.
6. [`docs/REPOSITORY_FOUNDATION.md`](docs/REPOSITORY_FOUNDATION.md) — repository-foundation rationale and intentionally deferred work.

## Kanoon article plugin

Plugin source:

```text
wp-content/plugins/ksh-kanoon-articles/
```

Plugin identity: **KSH Kanoon Articles** (`ksh-kanoon-articles`). Current development version: **0.4.0**.

The existing wp-admin surface under **Tools → آزمون اتصال مقاله‌های کانون** exposes three bounded actions:

- **Preview/Test Connection** — remote read + parse/validation only; it never mutates local snapshots or qualification state.
- **Refresh Local Data Now** — qualified Manual acquisition through the canonical refresh path; while the exact current acquisition contract is unqualified this action is blocked before remote acquisition and before snapshot/attempt/run-summary mutation.
- **دانلود گزارش JSON** — authenticated read-only download of current plugin/runtime evidence; it performs no remote acquisition, Refresh, option write, or Cron schedule mutation.

A separate protected Tools surface, **تأیید دریافت مقاله‌های کانون**, is the explicit Owner qualification action. It executes the exact current `Preview_Service` checks and records one bounded non-autoloaded qualification state only when both Latest and Weekly Popular succeed with non-empty valid results. Failed, ambiguous, zero-item, or qualification-state-write-failed executions do not qualify the contract.

The qualification source of truth is tied to an explicit acquisition-contract identity, currently:

```text
kanoon-homepage-semantic-lists-v1
```

This identity is intentionally independent from the plugin version. A future material source/parser contract change must use a new identity; qualification for an older identity is stale and cannot authorize Manual/Cron writes automatically.

Latest and Weekly Popular snapshots are independent. A failed, empty, malformed, or ambiguous candidate cannot erase a previous valid snapshot for that list. Per-list last-attempt metadata is stored separately from last-known-good data and carries explicit `manual` / `cron` origin, a bounded run identifier, and the producing `acquisition_contract_id` for new executions. Legacy attempts without contract identity remain readable as historical/unknown-contract evidence rather than being upgraded to current-contract proof.

Both current list acquisitions use `https://www.kanoon.ir/`, but they remain independently fetched and diagnosed. **Latest / `تازه‌ها`** is bound to the homepage's semantic `تازه‌ها` tab and its actual fragment target. **Weekly Popular / `پربازدید هفته`** keeps the semantic homepage tab/target parser with Monthly Popular as a sibling boundary. `/Article/Days` is no longer the public semantic source for Latest and is not used as a fallback under the `تازه‌ها` label.

The plugin uses WordPress Options for the small local state. The daily WP-Cron event is admitted only for a qualified current acquisition contract. The cheap `init` schedule-existence check clears an already-registered event left by a previous/stale contract while current qualification is absent, and the Cron callback itself independently checks admission before invoking writable refresh. This closes both scheduling and stale-event execution bypasses. Deactivation unschedules the hook but deliberately preserves valid snapshots and diagnostic evidence.

Schedule registration and actual Cron execution are intentionally separate facts. `wp_next_scheduled()` proves only that the owned event is registered. Newly persisted Manual/Cron run summaries carry the exact producing `acquisition_contract_id`; `cron_execution_observed=true` in the current diagnostic means a usable Cron summary explicitly belongs to the exact current acquisition contract. Legacy summaries with no contract id and summaries for a different/stale contract remain visible as historical evidence but cannot satisfy current-contract execution proof merely because they survived an upgrade. Qualification and execution evidence are reported independently.

It does **not** create Posts/CPTs, persist raw remote HTML, mirror article bodies/media, add a Gutenberg block/Elementor widget, or contact `kanoon.ir` while rendering public pages.

Public placement seam:

```text
[ksh_kanoon_articles]
```

The shortcode delegates to a reusable renderer that reads `Snapshot_Store::get_snapshot()` only. It renders the canonical `تازه‌های کانون` section with `تازه‌ها` and `پربازدید هفته`, preserves stored ordering, and displays at most the first **15** valid links from each list. This 15-item cap is presentation-only: a valid local snapshot may retain more items and the renderer does not rewrite or truncate storage. `date_context` remains part of validated local snapshot/diagnostic data but is intentionally not displayed in the public module.

Presentation is Persian RTL, responsive, scoped below `.ksh-kanoon-articles`, uses no frontend JavaScript, and lets each desktop panel keep its own natural content height. Article-link density is intentionally compact (approximately 14px at the normal 16px root scale) with tighter line-height/row spacing. KSH does not own font delivery: it inherits the site's active typography. With the exact Owner companion `rezahh107/Vazir` active, the site-delivered canonical family is `Vazirmatn`; KSH bundles no font files, defines no `@font-face`, and has no PHP/runtime dependency on that companion.

## Real-host qualification evidence

Three bounded historical real-host observations exist.

The Owner first executed the merged v0.1.0 read-only Preview on the real KSH WordPress host:

- Latest: PASS, 20 valid records, HTTP 200;
- Weekly Popular: PASS, 16 valid records, HTTP 200;
- WordPress version visibly observed: 7.1.2.

That execution used the then-current `/Article/Days` Latest implementation. It is useful historical evidence for the Preview/acquisition/parser boundary, but it does **not** qualify the v0.4.0 semantic change that now binds Latest to the homepage `تازه‌ها` list.

After merged PR #4, the Owner installed v0.2.1 and downloaded the plugin diagnostic JSON. That artifact observed:

- WordPress 7.1.2 and PHP 8.3.33;
- `event_registered=true`, recurrence `daily`;
- `cron_execution_observed=true` in that historical diagnostic version;
- one Cron-origin run with `overall_status=success`;
- Latest candidate/local count 20;
- Weekly Popular candidate/local count 16;
- `observability_incomplete=false` and diagnostic state `CRON_EXECUTION_OBSERVED`.

This proves the observed chain `WP-Cron → acquisition → parser/validation → independent local persistence → diagnostic persistence` for that historical execution. It does **not** guarantee future Cron firing, future Kanoon DOM/network stability, or the new homepage-Latest binding. Because that persisted run predates acquisition-contract provenance, the repaired v0.4.0 diagnostic classifies the surviving summary as `legacy_unknown_contract`; it remains readable historical evidence but does not make current-contract `cron_execution_observed` true.

The Owner also installed v0.3.0 and rendered `[ksh_kanoon_articles]` on the real KSH site. Desktop and mobile captures showed both lists rendering, the intended two-column desktop arrangement, one-column mobile stacking, and no obvious horizontal overflow in the provided mobile capture. v0.3.1 subsequently removed public per-item date metadata and default Grid equal-height stretching while preserving stored metadata.

The exact v0.4.0 contract `kanoon-homepage-semantic-lists-v1` remains **NOT_PROVEN on the real KSH host** until the Owner installs the repaired build and the explicit current-contract qualification action succeeds there. Repository tests and GitHub Actions must not be reported as that real-host qualification. Current-contract Manual/Cron execution also remains unobserved until a persisted execution summary carrying that exact contract identity is produced on the real host.

## Development verification

Development prerequisites:

- PHP CLI with DOM/libxml support;
- Composer 2;
- Git.

Canonical verification:

```bash
bash scripts/verify-foundation.sh
```

The command runs repository/design integrity checks, PHP syntax validation, WordPress Coding Standards, and deterministic parser/orchestration/persistence/lifecycle/qualification/diagnostic/frontend tests. Development dependencies are Composer `require-dev` packages only; the production plugin has no Composer runtime dependency.

Useful focused commands after `composer install`:

```bash
composer cs
composer test
```

Repository tests use bounded stubs for Options/WP-Cron/frontend lifecycle boundaries. Current coverage proves the homepage Latest semantic target, exclusion of unrelated/archive/foreign/malformed candidates, fail-closed semantic ambiguity, independent Latest/Weekly outcomes, preservation of complete source ordering in storage, the 15-per-list public cap, absence of public `date_context`, last-known-good preservation, current-contract qualification admission, stale scheduled-event blocking, failed qualification non-admission, Manual/Cron run-summary independence, contract-bound Manual/Cron and per-list-attempt provenance, legacy/stale/current evidence classification, schedule-vs-execution truthfulness, cross-contract correlation/incomplete-observability states, diagnostic privacy exclusions, degraded admin behavior, read-only diagnostics/export, local-only frontend rendering, output escaping, shortcode compatibility, and scoped responsive/font-inheritance CSS contracts. They prove only the exercised deterministic behavior; they do not qualify the real KSH host or guarantee future Cron firing, future Kanoon HTML compatibility, or authentic real-page visual acceptance of v0.4.0.

## Operational qualification workflow

The historical v0.2.1 diagnostic established one successful real Cron-origin execution for the historical contract. The JSON download remains the preferred bounded support artifact because it is read-only and does not contact `kanoon.ir`.

After this repaired v0.4.0 build is installed by the Owner on KSH:

1. keep the existing LKG snapshots and `[ksh_kanoon_articles]` placement intact;
2. run ordinary **Preview/Test Connection** and confirm the homepage `تازه‌ها` semantic list and Weekly Popular both succeed; Preview alone must leave the contract unqualified;
3. use **Tools → تأیید دریافت مقاله‌های کانون** to execute the explicit qualification action for `kanoon-homepage-semantic-lists-v1`;
4. only after that action reports successful persisted qualification, verify that the daily event is admitted and run **Refresh Local Data Now**;
5. verify a successful refresh updates local snapshots without visitor-time remote requests and that independent LKG behavior is preserved on a later list-specific failure;
6. verify public output displays at most 15 items per list while stored/diagnostic counts may be higher;
7. confirm article text resolves to the site's delivered `Vazirmatn` family when the exact `rezahh107/Vazir` frontend typography is enabled, while remaining readable if that companion is absent;
8. verify desktop remains two-column with natural panel heights and mobile remains one-column with no horizontal overflow;
9. download the diagnostic JSON and verify the current acquisition contract id, qualification status, Manual/Cron evidence provenance, scheduler state, and full local snapshot facts without public-only truncation.

Until step 3 succeeds on the real KSH host, Manual/Cron writable acquisition for the v0.4.0 contract is intentionally blocked and real-host qualification remains `NOT_PROVEN`. Until a real Manual/Cron execution then persists evidence carrying the exact current contract id, current-contract operational execution evidence also remains `NOT_PROVEN`.

## Product direction

- Responsive Persian RTL WordPress website.
- Public homepage and contact/about/service paths.
- Staff/manager-facing access to relevant forms and services.
- Direct student-registration entry path where required.
- A locally rendered `تازه‌ها / پربازدید هفته` module reading only validated local snapshots through `[ksh_kanoon_articles]`.
- No full remote-article mirroring.

## Historical Elementor public-homepage qualification artifact

The observed homepage qualification stack was **Hello Elementor 3.5.1 + Elementor 4.3.2 + Elementor Pro 4.3.0** on the KSH WordPress runtime. The repository still does not own a custom theme.

The current WordPress front page remains the existing Plato managers' portal (page ID 62, slug `plato-user-panel`, template `tpl-user-panel.php`). The Owner decision separates that portal from the public homepage. The observed global Elementor Theme Builder header **Header01** (template ID 341, general header condition) remains global chrome.

The merged PR #7 page-body artifact is retained at:

`elementor/homepage/ksh-public-homepage-body-v1.json`

It is **historical/technical qualification evidence only** and is **not the selected final homepage implementation method**. The Owner will manually build the final public pages in Elementor. Future work must not treat import/deployment of this JSON as the final homepage path.

Canonical verification still validates the retained artifact's deterministic contract through:

`php scripts/validate-elementor-homepage.php`

That check protects the historical artifact from silent drift; it does not prove or prescribe the final manually built homepage. See `elementor/homepage/README.md` for the artifact's evidence boundary, and `docs/SITE_INFORMATION_ARCHITECTURE.md` for the current proposed destination/navigation reference.

## License

No license has been declared yet. Do not add or infer a license without an explicit Owner decision.
