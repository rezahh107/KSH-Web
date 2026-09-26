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

## Current locked product decisions

- WordPress is the target platform; public UI is Persian RTL/responsive.
- Kanoon integration mirrors metadata/links only: Latest (~20) + Kanoon's Weekly Popular order.
- Normal visitor requests must never depend on live `kanoon.ir` acquisition.
- Acquisition/validation feeds independent per-list local last-known-good snapshots.
- Failed/empty/malformed/ambiguous candidates must not replace a valid snapshot.
- Approximately daily/nightly WordPress-native refresh is sufficient; no exact-minute scheduling requirement.
- No Posts/CPT import, article/media mirroring, local popularity calculation, visitor-time fallback, or frontend article module at the current stage.

See `docs/decisions/ADR-001-kanoon-article-list-mirror.md` for the locked architecture.

## Current implementation

Plugin: `wp-content/plugins/ksh-kanoon-articles/`.

Current backend capability includes:

- read-only Preview/Test Connection under Tools;
- Owner-observed real-host Preview success for Latest (20/HTTP 200) and Weekly Popular (16/HTTP 200) on the KSH host;
- independent non-autoloaded local snapshots + bounded per-list attempt state;
- one canonical manual/scheduled refresh service;
- one daily native WP-Cron hook with cheap upgrade-safe schedule self-healing;
- deactivation unscheduling without snapshot deletion.

The observed real-host Preview qualified that acquisition/parser path at that execution time. Persistence correctness, actual future cron firing, frontend behavior, future DOM/network stability, and exact production PHP remain separate evidence boundaries.

## Verification

Canonical repository verification:

```bash
bash scripts/verify-foundation.sh
```

It covers repository/design integrity, PHP syntax, WPCS, and deterministic parser/orchestration/persistence/lifecycle tests. Composer is development-only; production code has no Composer runtime dependency.

Focused checks after dependency installation:

```bash
composer cs
composer test
```

Do not treat stubs/CI as proof of real future WP-Cron execution or live source compatibility.

## Change boundaries

- Prefer the smallest sufficient change; preserve the accepted architecture.
- Keep Preview read-only and persistence/scheduling independent from future presentation.
- Do not add frontend rendering, custom DB tables, Action Scheduler, queues, generic scraping frameworks, release machinery, or unrelated website work incidentally.
- Never commit secrets, credentials, `wp-config.php`, or live environment files.
- Production deployment, destructive migration, and live-data mutation require explicit authorization.

## Definition of Done for repository changes

A change is complete only when:

- it preserves current project authority and scope;
- relevant status docs are truthful;
- the canonical verification command passes for the exact resulting Head;
- unexecuted runtime/production claims remain `NOT_PROVEN` rather than inferred.
