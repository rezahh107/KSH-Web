# KSH-Web

Official WordPress website project for **Kanoon Farhangi Amoozeshi Shiraz (کانون فرهنگی آموزشی شیراز)**.

The repository contains the site-specific **KSH Kanoon Articles** plugin. Its current backend capability covers qualified read-only acquisition plus independent local last-known-good snapshots, manual refresh, and one approximately-daily WordPress-native refresh schedule. The public/frontend article module is still intentionally not implemented.

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

Plugin identity: **KSH Kanoon Articles** (`ksh-kanoon-articles`). Current development version: **0.2.0**.

The wp-admin surface remains under **Tools → آزمون اتصال مقاله‌های کانون** and now separates two explicit actions:

- **Preview/Test Connection** — remote read + parse/validation only; it never mutates local snapshots.
- **Refresh Local Data Now** — remote acquisition through the same qualified candidate producer; only successful validated candidates replace the corresponding local list snapshot.

Latest and Weekly Popular snapshots are independent. A failed, empty, malformed, or ambiguous candidate cannot erase a previous valid snapshot for that list. Per-list last-attempt metadata is stored separately from last-known-good data.

The plugin uses WordPress Options for the small local state and one native daily WP-Cron hook. A cheap `init` schedule-existence check self-heals the schedule after an in-place plugin upgrade; this check never performs remote acquisition. Deactivation unschedules the hook but deliberately preserves valid snapshots.

It does **not** create Posts/CPTs, persist raw remote HTML, mirror article bodies/media, add a shortcode/block/widget, or render the public homepage module.

## Real-host qualification evidence

The Owner executed the merged v0.1.0 read-only Preview on the real KSH WordPress host before this persistence stage was authorized. Observed result:

- Latest: PASS, 20 valid records, HTTP 200, source `https://www.kanoon.ir/Article/Days`;
- Weekly Popular: PASS, 16 valid records, HTTP 200, source `https://www.kanoon.ir/`;
- WordPress version visibly observed: 7.1.2.

That execution qualified the observed path from the real KSH host through the WordPress HTTP API, current Kanoon HTML, parser, validation, and normalized results. It does **not** prove future DOM/network stability, persistence correctness, future WP-Cron execution, frontend behavior, or the exact production PHP version.

## Development verification

Development prerequisites:

- PHP CLI with DOM/libxml support;
- Composer 2;
- Git.

Canonical verification:

```bash
bash scripts/verify-foundation.sh
```

The command runs repository/design integrity checks, PHP syntax validation, WordPress Coding Standards, and deterministic parser/orchestration/persistence/lifecycle tests. Development dependencies are Composer `require-dev` packages only; the production plugin has no Composer runtime dependency.

Useful focused commands after `composer install`:

```bash
composer cs
composer test
```

Repository tests use bounded stubs for Options/WP-Cron lifecycle boundaries. They prove only the exercised deterministic behavior; they do not prove that the real site's future cron runner will fire or that future Kanoon HTML remains compatible.

## Next runtime gate

After this persistence/scheduling change is merged and installed, validate on the real KSH host that plugin upgrade succeeds, Preview still passes, manual refresh creates both snapshots, counts/timestamps/status are shown, a next WP-Cron event is registered, and ordinary page loads do not trigger remote acquisition. Actual future cron execution remains separately unproven until observed.

Do not proceed to frontend implementation until that real-host persistence/manual-refresh/schedule-registration validation is complete.

## Product direction

- Responsive Persian RTL WordPress website.
- Public homepage and contact/about/service paths.
- Staff/manager-facing access to relevant forms and services.
- Direct student-registration entry path where required.
- A future locally rendered `تازه‌ها / پربازدید هفته` module reading only validated local snapshots.
- No full remote-article mirroring.

## License

No license has been declared yet. Do not add or infer a license without an explicit Owner decision.
