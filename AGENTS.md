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
- Refresh may be approximately daily/nightly.
- Failed refreshes must preserve the previous last-known-good snapshot.
- The selected article acquisition family is a small site-specific WordPress extractor using WordPress Core HTTP APIs and local snapshot storage.
- Before enabling writes/scheduling, implementation must provide a read-only preview/qualification path on the real target host.

See `docs/decisions/ADR-001-kanoon-article-list-mirror.md` for the complete contract.

## Design authority

Use `docs/design/assets/homepage-responsive-reference.webp` together with `docs/design/UI_REFERENCE.md`.

The image is a composition/reference artifact, not textual-content authority. Preserve the responsive structure, visual language, hierarchy, and placement intent; do not reproduce obvious generated-image text errors as product copy.

## Verification

Current canonical repository verification:

```bash
bash scripts/verify-foundation.sh
```

At this stage there is no proven production build/lint/test stack yet. Do not invent one in prose. When code/tooling is introduced, extend the canonical verification path and update this file.

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
