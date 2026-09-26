# KSH-Web

Official WordPress website project for **Kanoon Farhangi Amoozeshi Shiraz (کانون فرهنگی آموزشی شیراز)**.

The repository now contains the first bounded production plugin foundation for the Kanoon article-list integration. The current implementation is deliberately **qualification-only**: it can run an explicit read-only Preview/Test Connection in wp-admin, but it does not persist article data, schedule refreshes, or render the public article module.

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

Plugin identity: **KSH Kanoon Articles** (`ksh-kanoon-articles`).

The current wp-admin surface is registered under **Tools → آزمون اتصال مقاله‌های کانون**. Merely opening the page does not contact `kanoon.ir`. A remote request occurs only after an authorized administrator submits the nonce-protected Preview action.

That Preview currently:

- fetches only the two approved public source pages with the WordPress HTTP API;
- parses Latest and Weekly Popular independently;
- validates canonical Kanoon article URLs and list identity;
- preserves source ordering;
- reports success, partial success, failure, or ambiguity without writing operational article state;
- shows bounded normalized evidence in the same request/response.

It does **not** create Posts/CPTs, store article snapshots/options/transients, schedule jobs, or change the frontend.

## Development verification

Development prerequisites for the current repository checks:

- PHP CLI with DOM/libxml support;
- Composer 2;
- Git.

Canonical verification remains:

```bash
bash scripts/verify-foundation.sh
```

The command preserves the existing repository/design integrity checks and now also runs PHP syntax validation, WordPress Coding Standards checks, and deterministic parser/orchestration tests. Development dependencies are Composer `require-dev` packages only; the production plugin has no Composer runtime dependency.

Useful focused commands after `composer install`:

```bash
composer cs
composer test
```

Repository fixtures prove only the parser/normalization/validation behavior they exercise. They do **not** prove that the real KSH WordPress host can reach `kanoon.ir`, that its DNS/TLS/firewall path works, or that the live source DOM still matches at execution time.

## Real-host qualification boundary

The next qualification step is to install the plugin directory on the real target WordPress host, activate it, open its Tools page, read the pre-execution side-effect notice, and explicitly run the Preview/Test Connection.

Until both required lists succeed there, production-host acquisition and exact live-source compatibility remain **`NOT_PROVEN`**. Persistence and scheduling must stay disabled until that evidence exists.

## Product direction

- Responsive Persian RTL WordPress website.
- Public homepage and contact/about/service paths.
- Staff/manager-facing access to relevant forms and services.
- Direct student-registration entry path where required.
- A future locally rendered `تازه‌ها / پربازدید هفته` module sourced from `kanoon.ir` metadata only.
- No full remote-article mirroring.

## License

No license has been declared yet. Do not add or infer a license without an explicit Owner decision.
