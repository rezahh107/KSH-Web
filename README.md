# KSH-Web

Official WordPress website project for **Kanoon Farhangi Amoozeshi Shiraz (کانون فرهنگی آموزشی شیراز)**.

The repository is currently in the **foundation / pre-implementation** stage. Product scope, design authority, and the first architecture decisions are documented before production code is introduced.

## Start here

1. [`docs/MOTHER_PROJECT.md`](docs/MOTHER_PROJECT.md) — canonical project charter, scope, locked decisions, boundaries, and current status.
2. [`docs/design/UI_REFERENCE.md`](docs/design/UI_REFERENCE.md) — responsive UI reference and its authority limits.
3. [`docs/decisions/ADR-001-kanoon-article-list-mirror.md`](docs/decisions/ADR-001-kanoon-article-list-mirror.md) — locked architecture for mirroring Kanoon article-list metadata.
4. [`AGENTS.md`](AGENTS.md) — concise operational entrypoint for AI-assisted engineering.
5. [`docs/REPOSITORY_FOUNDATION.md`](docs/REPOSITORY_FOUNDATION.md) — why this foundation exists, what was applied, and what was intentionally deferred.

## Current product direction

- Responsive Persian RTL WordPress website.
- Public homepage and contact/about/service paths.
- Staff/manager-facing access to relevant forms and services.
- Direct student-registration entry path where required.
- A locally rendered `تازه‌ها / پربازدید هفته` module sourced from `kanoon.ir` metadata only.
- No full remote-article mirroring.

## Canonical verification

At the current foundation stage:

```bash
bash scripts/verify-foundation.sh
```

This verifies the repository foundation and design-reference integrity only. It does **not** claim WordPress/runtime/product behavior. When implementation code is added, extend the same verification contract with the real code/toolchain checks rather than inventing a parallel path.

## Implementation status

Production theme/plugin code has not been established in this repository yet. Do not assume a final theme/plugin decomposition until it is explicitly selected and documented.

## License

No license has been declared yet. Do not add or infer a license without an explicit Owner decision.
