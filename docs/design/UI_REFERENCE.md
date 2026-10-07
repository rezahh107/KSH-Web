# UI Reference Contract

## Authority layers

The KSH visual authority is now layered rather than represented by one homepage image:

1. Whole-site guidance authority: `KSH_NINE_PAGE_DESIGN_SYSTEM_v1.0.1_COMPLETE.zip` with `DESIGN_SYSTEM_STATUS = COMPLETE`.
2. Current Page 05 implementation authority: `docs/design/PAGE05_PRODUCTION_REFERENCE.md`, registering `KSH_PAGE05_MASTER_REFERENCE_v1.1` and `KSH_PAGE05_HERO_REFERENCE_v1.0`.
3. Historical homepage/article-composition evidence: `docs/design/assets/homepage-responsive-reference.webp`.

A lower or older visual reference must not override a page-specific locked master or current explicit Owner instruction. Completion of a design artifact does not prove WordPress/Elementor/browser runtime behavior.

## Current Page 05 reference

Use `docs/design/PAGE05_PRODUCTION_REFERENCE.md` for Page 05 — Strategic Program / برنامه راهبردی.

Its canonical desktop/mobile masters are visual implementation authority, but:

`ELEMENTOR_RUNTIME_STATUS = NOT_PROVEN`

remains mandatory until native Elementor construction plus authentic browser/responsive/interaction qualification has actually run.

The 390px master is the primary mobile visual reference, not a command to force identical geometry at every mobile width. Responsive implementation must remain usable at 320, 360, 375, 390, 412, and 414px and at representative 768–1024px intermediate widths.

## Historical homepage visual asset

`docs/design/assets/homepage-responsive-reference.webp`

Repository identity:

- Git blob SHA: `4f821a2e0c3a03c897c28eefb50d8ac7312359ce`
- Size: `19888` bytes

This is a reduced, layout-focused WebP derivative of the earlier responsive homepage concept that integrated the Kanoon article-list module. It remains useful for the historical homepage/article composition it was created to describe. It is **not** the current whole-site design-system package and is **not** Page 05 authority.

## What the historical image is authoritative for

Within its original homepage scope, use it as evidence for:

- overall RTL composition;
- desktop/tablet/mobile responsive intent;
- content hierarchy and section rhythm;
- dark navy + white/light + warm gold/orange visual language;
- card treatment, spacing density, section markers, CTA hierarchy;
- service-card presentation;
- hero/header/footer relationship;
- placement and visual integration of the `تازه‌های کانون` module;
- desktop vs tablet/mobile adaptation of dense sections.

## What the image is NOT authoritative for

Do not treat rasterized/generated text inside the image as exact copy or factual content. It may contain imperfect Persian text, sample article titles, placeholder contact details, or illustrative labels.

It also does not override:

1. current explicit Owner instruction;
2. `docs/MOTHER_PROJECT.md`;
3. accepted ADRs;
4. `docs/design/PAGE05_PRODUCTION_REFERENCE.md` for Page 05;
5. actual approved copy/data.

It is not a pixel-perfect CSS specification and must not be generalized into an implementation mechanism for all pages.

## Article-list module interpretation

The historical concept shows the Kanoon article module integrated as a first-class KSH-Web section rather than embedding Kanoon's original UI.

Current content contract:

- Latest / `تازه‌ها`;
- Weekly Popular / `پربازدید هفته`;
- locally rendered links using KSH-Web styling;
- canonical click destination remains `kanoon.ir`;
- no full article body or remote widget iframe.

Responsive implementation may faithfully adapt density/layout while preserving hierarchy and avoiding a foreign embedded visual system.

## Guidance for language/vision models

When using KSH visual authority:

1. identify whether the task is whole-site guidance, Page 05, or historical homepage/article work;
2. use the highest applicable page-specific/current authority;
3. describe observed structure separately from assumptions;
4. preserve responsive intent rather than accidental raster geometry;
5. use canonical project docs for exact behavior/copy/destinations;
6. never upgrade static design evidence into Elementor/browser/accessibility proof.

## Asset maintenance

The historical homepage asset remains integrity-checked by `scripts/verify-foundation.sh` against its Git blob identity.

The current large Page 05 masters/Hero binaries remain in the established Owner Drive authority store. Their declared identity/status is source-controlled in `docs/design/PAGE05_PRODUCTION_REFERENCE.md` and checked for repository-side conformance by the canonical verifier; repository verification does not download or re-hash the Drive binary bytes. This deliberately avoids inventing a second binary-asset-management subsystem in the repository.

If a locked Page 05 asset is intentionally replaced, create a new versioned provenance record and update the repository identity contract only after explicit Owner approval.
