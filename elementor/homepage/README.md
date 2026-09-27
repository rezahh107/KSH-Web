# Elementor public homepage body

This directory owns the first source-controlled public-homepage **body** candidate for KSH-Web.

## Artifact

`ksh-public-homepage-body-v1.json`

Format: Elementor page-template JSON using the documented page data shape (`type: page`, data `version: 0.4`, `page_settings`, `content`). The artifact contains only page-body composition; it does not contain site settings, Theme Builder templates, WordPress users/content export, plugin state, or live-site configuration.

The candidate implements the largest authority-supported public subset:

- structural public hero for **کانون فرهنگی آموزشی شیراز / نمایندگی شیراز**;
- one expandable quick-access/service-card area containing only the confirmed manager portal destination `/plato-user-panel/`;
- one Elementor Shortcode widget containing `[ksh_kanoon_articles]` exactly once.

About, Contact, additional services/student paths, instructional/help sections, final CTA/footer enrichment, and final hero artwork remain deferred until authoritative copy, destinations, or production media exist.

## Real-host import/validation procedure

1. Keep the existing Plato page, Header01, footer state, and WordPress front-page assignment unchanged.
2. In Elementor's template library, import `ksh-public-homepage-body-v1.json`.
3. Create a **new** WordPress page for the public homepage and keep it Draft/private or otherwise non-front-page during validation.
4. Use the theme default or **Elementor Full Width** page layout that preserves Theme Builder chrome. Do **not** use Elementor Canvas if it removes Header01.
5. Insert/apply the imported page template to that new page.
6. Confirm Header01 remains visible and no duplicate header/footer is present in the body.
7. Confirm the existing manager action reaches `/plato-user-panel/` and the Plato page itself remains unchanged.
8. Confirm `[ksh_kanoon_articles]` renders from the accepted plugin/local-snapshot boundary.
9. Inspect representative widths around 1440 px, 1024 px, 768 px, and 390 px; confirm no horizontal overflow, natural Persian wrapping, usable article width, service-card reflow, readable spacing, and comfortable action targets.
10. Capture real-host screenshots/evidence.
11. Only after Owner acceptance should the Owner change **Settings → Reading** to assign the new public page as the homepage.

## Qualification boundary

Repository validation proves JSON/contract structure and guards the page-body boundaries. It does **not** prove that Elementor 4.3.2 has successfully imported this exact file or that the real KSH runtime renders it correctly at the target widths. Those claims remain `NOT_PROVEN` until the steps above are executed on the real host.
