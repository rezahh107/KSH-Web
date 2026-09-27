# KSH-Web — Mother Project Document

**Repository:** `rezahh107/KSH-Web`  
**Project:** Kanoon Farhangi Amoozeshi Shiraz Website  
**Platform:** WordPress  
**Primary language/direction:** Persian / RTL  
**Document role:** Canonical project charter and product/architecture authority for the repository.

---

## 1. Mission

Build and maintain the official WordPress website for **کانون فرهنگی آموزشی شیراز** as a responsive, clear, low-maintenance web presence that:

- presents the branch and its services;
- gives staff/managers direct access to relevant forms and service paths;
- provides direct public/student entry points where required;
- keeps the public homepage useful without turning the Shiraz site into a duplicated news/content publishing system;
- supports AI-assisted implementation through explicit project authority, design references, and verifiable repository contracts.

The repository should remain proportionate to the real project: prefer the smallest coherent solution that preserves correctness, required UX, maintainability, and operational reliability.

---

## 2. Current product model

The site is **not intended to become a high-volume dynamic/news site**.

Current intended public/product surfaces include:

- Homepage;
- Contact path/page;
- About/introduction path;
- service/navigation blocks linking users to relevant Kanoon services where appropriate;
- access paths for staff/branch managers to forms/services;
- direct student registration entry where required;
- a lightweight homepage module that surfaces current Kanoon article links from `kanoon.ir`.

Some content and exact page inventory may evolve during implementation. Do not infer additional product areas merely because WordPress can support them.

---

## 3. Design direction

The current responsive design reference is stored at:

`docs/design/assets/homepage-responsive-reference.webp`

Its interpretation contract is documented in:

`docs/design/UI_REFERENCE.md`

### Visual intent currently visible in the approved/reference direction

- Persian RTL layout;
- desktop, tablet, and mobile responsive behavior;
- clean white/light surfaces with a dark navy foundation;
- warm gold/orange accent for priority actions and section markers;
- card-based service/navigation sections;
- strong hierarchy with compact information density;
- prominent hero area;
- clear service/action cards;
- structured instructional/help sections;
- FAQ/important-notes/CTA/footer patterns;
- the Kanoon article-list module integrated into the existing page rhythm rather than visually behaving as an embedded foreign widget.

### Authority boundary

The visual reference is authoritative for **composition, hierarchy, responsive intent, visual language, and relative placement** within the limits described in `UI_REFERENCE.md`.

It is **not** authoritative for every generated Persian word, exact article title, exact phone number, URL, factual statement, or placeholder copy visible inside the image.

---

## 4. Homepage Kanoon article-list module — locked product decisions

The homepage should expose current useful links from `kanoon.ir` without copying full article content.

### Required lists

1. **تازه‌ها / Latest** — target capability: approximately the latest 20 valid article links.
2. **پربازدید هفته / Weekly Popular** — use Kanoon's own current source ordering.

`پربازدید ماه / Monthly Popular` is not currently required.

### Metadata-only contract

The integration should mirror only the smallest useful metadata, primarily:

- title;
- canonical article URL on `kanoon.ir`;
- date/day context when reliably available;
- source/list identity.

The canonical article remains hosted on `kanoon.ir` and clicking the item should take the visitor there.

Stored `date_context` remains backend/diagnostic metadata. The current Owner-approved public presentation intentionally does **not** display per-item date metadata.

### Explicit non-goals

Do not build, unless the Owner later changes scope:

- full article-body scraping/mirroring;
- remote article Posts/CPT lifecycle;
- image/media mirroring;
- duplicate article archive;
- popularity calculation owned by KSH-Web;
- iframe-based Kanoon UI embedding;
- real-time polling;
- visitor-time remote fetch fallback.

The full architecture contract is frozen in:

`docs/decisions/ADR-001-kanoon-article-list-mirror.md`

---

## 5. Article acquisition architecture — locked HOW

Selected implementation family:

**Small site-specific WordPress extractor + local last-known-good snapshot.**

Conceptual flow:

```text
kanoon.ir
    ↓
background acquisition (approximately daily/nightly)
    ↓
normalize + validate each list
    ↓
local WordPress snapshot
    ↓
KSH-Web UI reads local data only
```

### Locked reliability rules

- Normal visitor page loads must not wait for `kanoon.ir`.
- Daily/nightly freshness is sufficient; clock-level precision is unnecessary.
- A failed refresh must not erase a previously valid snapshot.
- Latest and Weekly Popular should be independently validatable so one failing list does not require destroying the other healthy list.
- Malformed/zero/ambiguous extraction must fail before replacement of valid local data.

### Qualification strategy and current evidence

A separate simulation laboratory is **not required as a prerequisite** for this feature.

The implementation requires a **read-only Preview/Test Connection qualification path on the real target WordPress host before writes/scheduling are enabled**. That gate has now been executed successfully by the Owner using the merged v0.1.0 plugin:

- Latest: PASS, 20 valid records, HTTP 200;
- Weekly Popular: PASS, 16 valid records, HTTP 200;
- WordPress version visibly observed on the real host: 7.1.2.

This qualified the observed runtime path from the real KSH host through WordPress HTTP acquisition, current Kanoon HTML, parser/validation, and normalized results at that execution.

After merged PR #4, the Owner installed plugin v0.2.1 and downloaded the real-host diagnostic JSON. That artifact observed WordPress 7.1.2, PHP 8.3.33, `event_registered=true`, daily recurrence, `cron_execution_observed=true`, a Cron-origin `overall_status=success`, Latest local_count=20, Weekly Popular local_count=16, and `observability_incomplete=false`. Therefore one real chain from WP-Cron through acquisition, validation, independent local persistence, and diagnostic persistence is proven for that observed execution.

The Owner subsequently installed v0.3.0, placed `[ksh_kanoon_articles]` on a real KSH page, and supplied desktop/mobile captures. Those captures showed both lists rendering, the intended two-column desktop composition, one-column mobile stacking, and no obvious horizontal overflow in the provided mobile capture. Public placement/rendering itself is therefore no longer wholly `NOT_PROVEN` for that observed execution. The same evidence exposed repeated public Latest date lines and equal-height Grid stretching; v0.3.1 removes those presentation artifacts while preserving the underlying stored metadata and local-only architecture.

These runtime observations do not guarantee future Cron firing or future Kanoon DOM/network stability. Final visual acceptance remains open until the refined v0.3.1 presentation is revalidated on the real page.

---

## 6. Known source targets for article metadata

Current bounded source direction:

- Latest: `https://www.kanoon.ir/Article/Days`
- Weekly Popular: `https://www.kanoon.ir/`

Previous inspection established useful server-returned article content and no usable public native RSS feed was discovered for the target flow. This is not permission to depend on a hidden/undocumented endpoint as a production contract.

If live source structure changes materially, repair the smallest extraction boundary needed; do not reopen the broader product architecture unless direct evidence falsifies it.

---

## 7. WordPress implementation boundaries

Current platform decision: **WordPress**.

The Kanoon article-list responsibility lives as a **small site-specific WordPress plugin-level component**, independent enough from presentation that source parsing/refresh can be maintained without coupling visitor UI to remote availability.

Current plugin implementation includes read-only Preview, independent local last-known-good snapshots, bounded per-list attempt state, explicit Manual/Cron refresh attribution through one canonical refresh service, one approximately-daily native WP-Cron path, separate latest Manual/Cron run summaries, and a one-click read-only JSON diagnostic export from the existing Tools page.

The public presentation layer is implemented as a reusable local-snapshot renderer plus the standard `[ksh_kanoon_articles]` shortcode. It reads only `Snapshot_Store::get_snapshot()`, never triggers acquisition/refresh, fails softly when one or both lists are unavailable, and uses one small fully scoped responsive RTL stylesheet. Stored `date_context` remains validated snapshot/diagnostic metadata but is not emitted in public item markup. The desktop grid keeps two columns while aligning panels to the start so each panel retains its natural content height; mobile remains a single-column stack. There is no Elementor-specific or Gutenberg-specific business/data implementation.

Use WordPress-native APIs where appropriate and keep environment-specific secrets out of Git.

---

## 8. Content and navigation direction

Current intended direction includes:

- clear introduction of the Shiraz branch;
- About path from introductory content;
- Contact path;
- service/action blocks;
- links to relevant official Kanoon resources (including strategic-program/service destinations where appropriate);
- staff/manager service access;
- forms/workflows already owned by the relevant systems;
- direct student registration URL/path when required.

Do not duplicate external service logic in KSH-Web merely to make the site appear more self-contained.

---

## 9. Authority map

For repository work, use this order:

1. current explicit Owner instruction;
2. this Mother Project document;
3. accepted ADRs under `docs/decisions/`;
4. `docs/design/UI_REFERENCE.md` and its referenced visual asset within their stated authority;
5. implementation contracts/tests/configuration;
6. ordinary code comments/examples.

A lower source must not silently override a higher one.

---

## 10. Repository / AI operating model

The repository should be:

**SELF-EXPLAINING + SELF-VALIDATING**

AI-assisted engineering is intended. The concise agent entrypoint is `AGENTS.md`; detailed product/architecture knowledge belongs in canonical docs rather than being duplicated into always-loaded instructions.

Current verification contract:

```bash
bash scripts/verify-foundation.sh
```

As implementation evolves, extend this same canonical verification path with applicable build/lint/static-analysis/test/runtime checks rather than creating parallel entrypoints.

---

## 11. Current status

### Confirmed / locked

- repository identity: `KSH-Web`;
- WordPress platform;
- Persian RTL responsive direction;
- current UI reference imported into the repository;
- metadata-only Kanoon article-list feature;
- Latest + Weekly Popular scope;
- approximately daily/nightly background refresh;
- independent local last-known-good snapshot semantics;
- no visitor-time remote dependency;
- small custom WordPress extractor family;
- real-host read-only Preview gate before writes/scheduling;
- Owner-executed real-host Preview PASS for Latest and Weekly Popular;
- Owner-observed real-host v0.2.1 Cron execution on WordPress 7.1.2 / PHP 8.3.33 with `overall_status=success`, Latest local_count=20, Weekly local_count=16, and complete persisted observability;
- Owner-observed real-host v0.3.0 shortcode placement/rendering on desktop and mobile, including both lists and responsive stacking.

### Implemented backend/runtime stage

- read-only Preview/Test Connection;
- independent validated per-list snapshot storage using WordPress Options;
- bounded per-list latest-attempt status with explicit origin/run identity for new attempts and legacy `unknown` compatibility;
- explicit Manual and scheduled Cron entry paths that reuse the same canonical acquisition/validation/persistence implementation;
- separate non-autoloaded latest Manual and latest Cron run summaries, without unbounded history;
- one native daily WP-Cron hook with upgrade-safe schedule existence repair;
- one-click authenticated JSON diagnostic download containing only bounded plugin-owned/public metadata and safe WordPress/PHP runtime facts;
- deterministic separation between schedule registration and persisted Cron execution evidence;
- deactivation unscheduling without deleting valid snapshots or diagnostic run summaries.

### Implemented public/frontend stage

- reusable renderer owned by `ksh-kanoon-articles`;
- standard placement seam: `[ksh_kanoon_articles]`;
- renderer reads only the existing local snapshot boundary;
- Latest and Weekly Popular preserve stored ordering and render all available validated items;
- stored Latest `date_context` remains available to backend/diagnostic consumers but is not publicly rendered;
- one-list availability renders cleanly and zero-list availability returns no module;
- output is escaped at the public boundary and malformed local state fails closed;
- Persian RTL two-column/stacked responsive presentation with fully scoped CSS and no frontend JavaScript;
- desktop panels use natural content height rather than equal-height Grid stretching;
- no Elementor widget, Gutenberg block, visitor-time remote fallback, or public diagnostics panel.

### Still NOT_PROVEN

- future Kanoon DOM/network stability;
- every future WP-Cron execution after the one observed v0.2.1 run;
- final real-host/browser visual acceptance of the v0.3.1 refinement at representative desktop/mobile widths;
- production deployment procedure beyond the Owner's normal plugin installation/content-placement path.

Repository tests may prove exercised deterministic behavior, but they must not be used to upgrade these remaining runtime/browser facts.

---

## 12. Near-term implementation sequence

The acquisition/persistence/scheduler runtime gate is qualified for the observed v0.2.1 real-host execution, and the v0.3.0 shortcode placement/rendering path has also been observed on the real site. The remaining frontend gate is the narrowly refined v0.3.1 visual revalidation.

After this refinement PR is merged, the safest real-host validation is:

1. install/update plugin v0.3.1 through the Owner's normal WordPress path;
2. keep the existing `[ksh_kanoon_articles]` placement in place;
3. load the real page and verify Latest + Weekly Popular still render from current local snapshots;
4. capture representative desktop (~1440 px) and mobile (~390 px) widths;
5. confirm repeated per-item Latest date lines are absent;
6. confirm the shorter desktop panel ends at its own natural content height rather than stretching to the taller panel;
7. confirm mobile still stacks to one column with no horizontal scrolling;
8. confirm page rendering does not trigger a refresh/acquisition;
9. download the diagnostic JSON again and verify stored snapshot date metadata, Cron, and observability state remain intact.

The diagnostic download remains the preferred bounded support artifact and must stay read-only. Future source compatibility and future Cron runs remain operational evidence questions, not assumptions.

For the broader site, implementation should continue to follow the accepted visual structure and actual WordPress environment rather than creating speculative infrastructure.

---

## 13. Non-goals for repository foundation

This project intentionally does not add without demonstrated need or Owner decision:

- a license decision;
- CODEOWNERS for a single-owner topology;
- issue-form bureaucracy;
- release machinery before a release model exists;
- Git LFS for the current small reference asset;
- speculative dependency/security automation;
- unrelated infrastructure for the article integration.

---

## 14. Qualified Elementor public-homepage path

Fresh Owner-qualified presentation evidence establishes the current target stack for public homepage composition as:

- WordPress 7.1.2 / PHP 8.3.33;
- Hello Elementor 3.5.1;
- Elementor 4.3.2;
- Elementor Pro 4.3.0;
- Persian site language `fa_IR`;
- existing global Theme Builder header `Header01` (template ID 341, general header condition);
- existing current front page is the Plato managers' portal (page ID 62, slug `plato-user-panel`, template `tpl-user-panel.php`).

The Owner decision now explicitly separates the future public homepage from the managers' portal. The Plato page remains independently reachable and must not be recreated, renamed, deleted, or absorbed into the public page.

The selected source-controlled public-homepage implementation path is a **bounded Elementor page-body template**, not a custom theme, child theme, whole-site kit, or database-only manual composition. The versioned candidate lives at:

`elementor/homepage/ksh-public-homepage-body-v1.json`

The v1 candidate intentionally implements only the authority-supported subset: public identity hero, confirmed manager portal access, and the accepted `[ksh_kanoon_articles]` placement. About/Contact copy, additional service/student destinations, instructional sections, final CTA/footer enrichment, and final hero media remain deferred until authoritative content exists.

The artifact must preserve global Theme Builder chrome and therefore must be applied to a new draft page using a layout that retains Header01; Elementor Canvas is not the selected deployment path when it removes the global header. The live `page_on_front` assignment remains an Owner acceptance action after real-host validation, not a repository change.

Repository checks can qualify JSON structure, boundary constraints, shortcode cardinality, responsive metadata, and other deterministic template contracts. They do not prove successful import into Elementor 4.3.2 or authentic browser rendering at the target widths. Those exact real-host claims remain `NOT_PROVEN` until import and visual validation are executed.

