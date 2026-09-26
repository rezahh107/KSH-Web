# UI Reference Contract

## Canonical visual asset

`docs/design/assets/homepage-responsive-reference.webp`

Repository identity:

- Git blob SHA: `4f821a2e0c3a03c897c28eefb50d8ac7312359ce`
- Size: `19888` bytes

The asset is a reduced, layout-focused WebP derivative of the current responsive concept that integrates the Kanoon article-list module into the existing page structure. Its purpose is fast visual grounding for humans and language/vision models; exact product copy comes from canonical project documents, not from rasterized text in the image.

## What the image is authoritative for

Use it as design evidence for:

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

Do not treat the rasterized/generated text inside the image as exact copy or factual content.

The image may contain imperfect or generated Persian text, sample article titles, placeholder phone/contact details, or illustrative labels. Those must not override:

1. current Owner instruction;
2. `docs/MOTHER_PROJECT.md`;
3. accepted ADRs;
4. actual approved copy/data.

The image is also not a pixel-perfect CSS specification unless the Owner later freezes it as such.

## Article-list module interpretation

The concept shows the Kanoon article module integrated as a first-class KSH-Web section rather than embedding Kanoon's original UI.

Current content contract:

- Latest / `تازه‌ها`;
- Weekly Popular / `پربازدید هفته`;
- locally rendered links using KSH-Web styling;
- canonical click destination remains `kanoon.ir`;
- no full article body or remote widget iframe.

Responsive implementation may use columns, tabs, collapsible density, or another faithful adaptation as long as it preserves the information hierarchy and does not introduce a foreign embedded visual system.

## Guidance for language/vision models

When using this asset:

1. inspect the image before proposing UI changes;
2. describe the observed structure separately from assumptions;
3. preserve responsive intent, not accidental raster artifacts;
4. use canonical project docs for exact product behavior and labels;
5. if a requested change conflicts with the image, follow the higher authority and update this reference contract/artifact only when explicitly authorized.

## Asset maintenance

The design asset is integrity-checked by `scripts/verify-foundation.sh` against its Git blob identity.

If the design reference is intentionally replaced:

- update the asset;
- update its recorded blob identity here and in the verification script;
- document the design-authority change in the relevant project/decision record.
