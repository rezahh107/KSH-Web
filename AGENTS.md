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
- Failed refreshes must preserve the previous last-known-good snapshot once persistence exists.
- The selected article acquisition family is a small site-specific WordPress extractor using WordPress Core HTTP APIs and local snapshot storage.
- Before enabling writes/scheduling, implementation must provide a read-only preview/qualification path on the real target host.

See `docs/decisions/ADR-001-kanoon-article-list-mirror.md` for the complete contract.

## Current implementation

The first bounded plugin implementation lives at:

`wp-content/plugins/ksh-kanoon-articles/`

Current admitted capability is only the explicit, read-only wp-admin Preview/Test Connection under Tools. It has no production article persistence, scheduled refresh, or frontend article rendering.

Important boundaries:

- opening the admin page and plugin activation must not contact `kanoon.ir`;
- remote acquisition runs only on the authorized nonce-protected Preview action;
- Latest and Weekly Popular remain independently reportable;
- fixture tests do not prove the production host/network/live DOM;
- production-host acquisition remains `NOT_PROVEN` until the Preview succeeds on the real KSH host.

Do not add persistence, scheduling, frontend presentation, or production deployment as an incidental extension of work on this stage.

## Design authority

Use `docs/design/assets/homepage-responsive-reference.webp` together with `docs/design/UI_REFERENCE.md`.

The image is a composition/reference artifact, not textual-content authority. Preserve the responsive structure, visual language, hierarchy, and placement intent; do not reproduce obvious generated-image text errors as product copy.

## Verification

Canonical repository verification:

```bash
bash scripts/verify-foundation.sh
```

It now covers the existing foundation/design integrity plus the admitted plugin source with PHP syntax, WPCS, and deterministic parser/orchestration tests. `composer.json` is development tooling only; do not introduce a production Composer runtime dependency unless a future product capability genuinely requires one.

Focused checks after dependency installation:

```bash
composer cs
composer test
```

Do not treat static/unit/fixture success as proof of WordPress host networking or live source compatibility.

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
- relevant docs/ADRs are updated when the contract changes;
- the canonical verification command passes for the exact resulting Head;
- any unexecuted runtime/production claims remain explicitly `NOT_PROVEN` rather than being inferred.
