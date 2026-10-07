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

The KSH Nine-Page Guidance Design System is now the locked whole-site design authority:

- canonical package: `KSH_NINE_PAGE_DESIGN_SYSTEM_v1.0.1_COMPLETE.zip`;
- `DESIGN_SYSTEM_STATUS = COMPLETE`;
- completion of the Design System does **not** mean Pages 01–09 are implemented in WordPress/Elementor.

The immediate page-specific implementation authority is Page 05 — Strategic Program / برنامه راهبردی. Its repository contract is:

`docs/design/PAGE05_PRODUCTION_REFERENCE.md`

That document registers the current locked Page 05 Master v1.1, canonical Hero v1.0, official asset identities, responsive/runtime qualification requirements, and the boundary between source-controlled provenance and the authoritative large binary assets retained in the established Owner Drive authority store.

The older repository asset:

`docs/design/assets/homepage-responsive-reference.webp`

remains valid **historical homepage/article-composition evidence** within the limits documented by `docs/design/UI_REFERENCE.md`. It is not the current whole-site or Page 05 implementation master and must not override the Nine-Page Design System or Page 05-specific authority.

### Current Page 05 authority

- `KSH_PAGE05_MASTER_REFERENCE_v1.1` — OWNER APPROVED / CANONICAL / LOCKED;
- desktop master: `KSH_PAGE05_MASTER_DESKTOP_v1.1.png`, 1440 × 3515, SHA-256 `2b2a783fd98b68ec97867f7a686cfcc79993c7ee3e3bf60aff77a965f8b81597`;
- mobile/responsive master: `KSH_PAGE05_MASTER_MOBILE_RESPONSIVE_v1.1.png`, 390 × 3904, SHA-256 `8877db443487d5d9e0f5a293c713e76a3397f5bda5c03f974174f612a6f8ab37`;
- `KSH_PAGE05_HERO_REFERENCE_v1.0` — VERIFIED / CANONICAL;
- `ELEMENTOR_RUNTIME_STATUS = NOT_PROVEN` until authentic Elementor/browser qualification is actually executed;
- immediate milestone: `PAGE_05_ELEMENTOR_PRODUCTION_REFERENCE`.

Page 05 v1.1 is bounded production hardening, not redesign authority. Preserve the approved content architecture, semantic section order, Hero concept, text hierarchy, Academic Journey grammar, Current Information concept, FAQ grammar, Related Guides grammar, KSH palette, typography direction, icon language, and official Kanoon logo authority.

Do not invent public copy, destinations, or permanent URLs merely to simplify implementation.

---

## 4. Homepage Kanoon article-list module — locked product decisions

The homepage should expose current useful links from `kanoon.ir` without copying full article content.

### Required lists

1. **تازه‌ها / Latest** — the semantic `تازه‌ها` list shown on the `kanoon.ir` homepage.
2. **پربازدید هفته / Weekly Popular** — use Kanoon's own current homepage source ordering.

`پربازدید ماه / Monthly Popular` is not currently a public KSH list; it remains useful only as a semantic sibling/boundary for Weekly parsing.

### Public display count

The public module displays at most the first **15** valid links from each owned list while preserving source ordering.

This is a presentation rule, not an acquisition/storage cap. If the semantic source contains more than 15 valid owned items, the local snapshot may preserve the complete validated list and diagnostics may report that full count. Do not discard valid stored data merely to equalize panel height.

### Metadata-only contract

The integration should mirror only the smallest useful metadata, primarily:

- title;
- canonical article URL on `kanoon.ir`;
- date/day context when reliably available;
- source/list identity.

The canonical article remains hosted on `kanoon.ir` and clicking the item should take the visitor there.

Stored `date_context` remains backend/diagnostic metadata. The current Owner-approved public presentation intentionally does **not** display per-item date metadata. Homepage Latest may persist an empty `date_context` when no reliable bounded date can be associated with the article; dates must not be guessed.

### Typography ownership

KSH Kanoon Articles owns only its component-level typography density and local presentation: font size, line height, weight where appropriate, spacing/padding, focus, RTL, responsive layout, and related local styling.

The exact Owner companion typography plugin is `rezahh107/Vazir`. Its canonical delivered CSS family is `Vazirmatn`. KSH must normally inherit the site-delivered family and must not bundle font files, add `@font-face`, invent a `Vazir` alias, download fonts, or create a runtime PHP dependency on the companion plugin.

### Explicit non-goals

Do not build, unless the Owner later changes scope:

- full article-body scraping/mirroring;
- remote article Posts/CPT lifecycle;
- image/media mirroring;
- duplicate article archive;
- popularity calculation owned by KSH-Web;
- iframe-based Kanoon UI embedding;
- real-time polling;
- visitor-time remote fetch fallback;
- forced equal-height article panels or nested article-list scroll containers.

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
- Sharing the same current homepage URL does not require a shared-fetch/cache subsystem; independently attributable fetch/parse outcomes are acceptable at this frequency.

### Qualification strategy and current evidence

A separate simulation laboratory is **not required as a prerequisite** for this feature.

The implementation requires a **read-only Preview/Test Connection qualification path on the real target WordPress host before writes/scheduling are enabled**. This is enforced as runtime admission, not merely as documented sequencing.

The exact acquisition/parser contract has an explicit identity independent from the plugin version. The current KSH Kanoon Articles release is v0.4.1. Its acquisition/parser contract identity remains:

```text
kanoon-homepage-semantic-lists-v1
```

One bounded non-autoloaded qualification record is authoritative only when it names that exact identity and records successful current-contract qualification. Any material source/parser contract change must use a new identity; historical qualification from another identity becomes stale automatically.

Ordinary Preview remains read-only and never mutates qualification state. A separate capability/nonce-protected Owner action executes the exact current `Preview_Service` checks and may persist qualification only if both Latest and Weekly Popular succeed with non-empty valid results. Failed, partial, ambiguous, zero-item, unavailable, or state-write-failed qualification does not admit writes.

While the exact current contract is unqualified:

- Manual/Cron refresh is blocked before remote acquisition and before snapshot/attempt/run-summary mutation;
- existing LKG snapshots and public local rendering remain available;
- no new recurring event is scheduled;
- any stale recurring event left by a previous contract is cleared by schedule self-healing;
- the Cron callback independently fails closed even if a stale event is invoked before cleanup.

That gate was historically executed successfully by the Owner using the merged v0.1.0 plugin:

- Latest: PASS, 20 valid records, HTTP 200;
- Weekly Popular: PASS, 16 valid records, HTTP 200;
- WordPress version visibly observed on the real host: 7.1.2.

That historical execution used the then-current `/Article/Days` Latest parser. It qualified the observed runtime path for that exact historical source/parser execution; it does **not** qualify `kanoon-homepage-semantic-lists-v1`.

After merged PR #4, the Owner installed plugin v0.2.1 and downloaded the real-host diagnostic JSON. That artifact observed WordPress 7.1.2, PHP 8.3.33, `event_registered=true`, daily recurrence, `cron_execution_observed=true`, a Cron-origin `overall_status=success`, Latest local_count=20, Weekly Popular local_count=16, and `observability_incomplete=false`. Therefore one real chain from WP-Cron through acquisition, validation, independent local persistence, and diagnostic persistence is proven for that historical execution.

The Owner subsequently installed v0.3.0, placed `[ksh_kanoon_articles]` on a real KSH page, and supplied desktop/mobile captures. Those captures showed both lists rendering, the intended two-column desktop composition, one-column mobile stacking, and no obvious horizontal overflow in the provided mobile capture. v0.3.1 removed repeated public Latest date lines and equal-height Grid stretching while preserving the underlying stored metadata and local-only architecture.

These runtime observations do not guarantee future Cron firing, future Kanoon DOM/network stability, or the new homepage-Latest semantic binding. Real-host qualification of `kanoon-homepage-semantic-lists-v1` remains **NOT_PROVEN** until the Owner executes the current v0.4.1 qualification action successfully on KSH.

---

## 6. Known source targets for article metadata

Current bounded source direction:

- Latest / `تازه‌ها`: `https://www.kanoon.ir/`, semantically bound from the `تازه‌ها` tab label to its actual target/container;
- Weekly Popular / `پربازدید هفته`: `https://www.kanoon.ir/`, semantically bound to its own target with Monthly Popular as the sibling boundary.

`https://www.kanoon.ir/Article/Days` is no longer the current public semantic source for Latest and must not be used as a silent fallback under the `تازه‌ها` label.

Previous inspection established useful server-returned article content and no usable public native RSS feed was discovered for the target flow. This is not permission to depend on a hidden/undocumented endpoint as a production contract.

If live source structure changes materially, repair the smallest extraction boundary needed; do not reopen the broader product architecture unless direct evidence falsifies it.

---

## 7. WordPress implementation boundaries

Current platform decision: **WordPress**.

The Kanoon article-list responsibility lives as a **small site-specific WordPress plugin-level component**, independent enough from presentation that source parsing/refresh can be maintained without coupling visitor UI to remote availability.

Current plugin implementation includes read-only Preview, a separate current-contract qualification Owner action, one bounded qualification record tied to the explicit acquisition-contract identity, independent local last-known-good snapshots, bounded per-list attempt state, qualification-gated Manual/Cron refresh attribution through one canonical refresh service, one qualified approximately-daily native WP-Cron path with stale-event cleanup and callback admission, separate latest Manual/Cron run summaries, and a one-click read-only JSON diagnostic export from the existing Tools page.

The public presentation layer is implemented as a reusable local-snapshot renderer plus the standard `[ksh_kanoon_articles]` shortcode. It reads only `Snapshot_Store::get_snapshot()`, never triggers acquisition/refresh, fails softly when one or both lists are unavailable, and uses one small fully scoped responsive RTL stylesheet. Stored `date_context` remains validated snapshot/diagnostic metadata but is not emitted in public item markup. The renderer displays at most 15 links per list without mutating/truncating the underlying snapshot. The desktop grid keeps two columns while aligning panels to the start so each panel retains its natural content height; mobile remains a single-column stack. There is no Elementor-specific or Gutenberg-specific business/data implementation.

KSH presentation inherits the active site font family. `rezahh107/Vazir` owns `Vazirmatn` font asset/family delivery when enabled; KSH owns no font asset delivery and remains functional if that companion is absent.

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

- KSH Nine-Page Guidance Design System v1.0.1 is `COMPLETE / LOCKED`;
- Page 05 Master v1.1 is the current Owner-approved canonical implementation reference;
- Page 05 Hero v1.0 is `VERIFIED / CANONICAL`;
- `PAGE_05_ELEMENTOR_PRODUCTION_REFERENCE` is the immediate project milestone;
- current KSH Kanoon Articles release family is v0.4.1; its current-contract real-host qualification remains a valid gap but is deferred from immediate priority;
- Pages 01–04 and 06–09 are not upgraded to implemented/runtime-proven status merely because the Nine-Page Design System is complete;
- repository identity: `KSH-Web`;
- WordPress platform;
- Persian RTL responsive direction;
- current UI reference imported into the repository;
- metadata-only Kanoon article-list feature;
- Latest + Weekly Popular scope;
- Latest semantic source is the homepage `تازه‌ها` target, not `/Article/Days`;
- public display is capped at 15 links per list while valid stored lists may be longer;
- approximately daily/nightly background refresh after exact-contract qualification;
- independent local last-known-good snapshot semantics;
- no visitor-time remote dependency;
- small custom WordPress extractor family;
- exact acquisition-contract qualification admission before writes/scheduling;
- current acquisition-contract identity `kanoon-homepage-semantic-lists-v1` is independent from plugin version;
- historical Owner-executed real-host Preview PASS for the then-current Latest/Weekly parser paths does not qualify the current identity;
- historical Owner-observed real-host v0.2.1 Cron execution on WordPress 7.1.2 / PHP 8.3.33 with `overall_status=success`, Latest local_count=20, Weekly local_count=16, and complete persisted observability;
- historical Owner-observed real-host v0.3.0 shortcode placement/rendering on desktop and mobile, including both lists and responsive stacking;
- KSH typography inherits site font-family ownership; the exact `rezahh107/Vazir` companion owns self-hosted `Vazirmatn` delivery when enabled.

### Implemented backend/runtime stage

- read-only Preview/Test Connection that never silently mutates qualification state;
- separate protected Owner qualification action using the exact current Preview service;
- bounded non-autoloaded current-contract qualification state with persistence readback;
- fail-closed Manual/Cron refresh admission before acquisition and all snapshot/attempt/run-summary mutation;
- independent validated per-list snapshot storage using WordPress Options;
- bounded per-list latest-attempt status with explicit origin/run identity for new attempts and legacy `unknown` compatibility;
- explicit Manual and scheduled Cron entry paths that reuse the same canonical acquisition/validation/persistence implementation after qualification;
- separate non-autoloaded latest Manual and latest Cron run summaries, without unbounded history;
- one native daily WP-Cron hook only for a qualified current contract, with upgrade-safe stale-event cleanup;
- independent Cron callback qualification guard preventing a stale registered event from bypassing admission;
- one-click authenticated JSON diagnostic download containing only bounded plugin-owned/public metadata and safe WordPress/PHP runtime facts;
- deterministic separation between schedule registration and persisted Cron execution evidence;
- deactivation unscheduling without deleting valid snapshots or diagnostic run summaries;
- v0.4.0 Latest parser binding to the homepage `تازه‌ها` semantic target and fail-closed behavior for missing/duplicate/colliding Latest boundaries;
- no acquisition cap tied to the 15-item public presentation limit.

### Implemented public/frontend stage

- reusable renderer owned by `ksh-kanoon-articles`;
- standard placement seam: `[ksh_kanoon_articles]`;
- renderer reads only the existing local snapshot boundary;
- Latest and Weekly Popular preserve stored ordering;
- public renderer displays at most the first 15 valid items from each list while leaving longer snapshots intact;
- stored Latest `date_context` remains available to backend/diagnostic consumers but is not publicly rendered;
- one-list availability renders cleanly and zero-list availability returns no module;
- output is escaped at the public boundary and malformed local state fails closed;
- Persian RTL two-column/stacked responsive presentation with fully scoped CSS and no frontend JavaScript;
- desktop panels use natural content height rather than equal-height Grid stretching;
- article typography uses a denser local scale/spacing while inheriting the site-delivered font family;
- no bundled fonts, `@font-face`, invented `Vazir` family alias, Elementor widget, Gutenberg block, visitor-time remote fallback, or public diagnostics panel.

### Still NOT_PROVEN

- `ELEMENTOR_RUNTIME_STATUS = NOT_PROVEN` for Page 05 until native Elementor construction and authentic browser/runtime qualification are executed;
- Page 05 URL/publication binding remains unproven unless an already-authoritative WordPress page/path is found in the runtime; repository IA proposals alone do not authorize a permanent public path;
- Pages 01–04 and 06–09 remain not implemented/not runtime-qualified unless separately proven;
- KSH Kanoon Articles v0.4.1 current-contract real-host qualification remains unresolved and deferred, not silently closed;
- future Kanoon DOM/network stability;
- real-host successful qualification of `kanoon-homepage-semantic-lists-v1` through the current v0.4.1 Owner action on KSH;
- real-host writable Manual/Cron execution for the current v0.4.1 release after that qualification;
- every future WP-Cron execution after the historical observed v0.2.1 run;
- real-host/browser visual acceptance of the current v0.4.1 release at representative desktop/mobile widths, including actual `Vazirmatn` resolution when the companion typography plugin is active;
- production deployment procedure beyond the Owner's normal plugin installation/content-placement path.

Repository tests may prove exercised deterministic behavior and admission enforcement, but they must not be used to upgrade these remaining runtime/browser facts.

---

## 12. Near-term implementation sequence

The KSH Nine-Page Guidance Design System v1.0.1 is complete and locked. The active immediate milestone is now:

`PAGE_05_ELEMENTOR_PRODUCTION_REFERENCE`

Execution order:

1. synchronize repository authority with the locked Nine-Page/Page 05 design state without rewriting historical article/homepage facts;
2. inspect the authentic KSH WordPress/Elementor runtime before page mutation, including the actual Page 05 page/draft situation, global styles, `Header01`, typography delivery, breakpoints, and reusable native Elementor primitives;
3. construct Page 05 natively in Elementor from `KSH_PAGE05_MASTER_REFERENCE_v1.1` using the canonical Hero/assets and bounded CSS only where Elementor controls are insufficient;
4. preserve RTL semantic reading order and content-driven/intrinsic height; do not trace the screenshot with brittle absolute coordinates;
5. qualify 1440 desktop, representative 768–1024 intermediate widths, and 320/360/375/390/412/414 mobile widths, including Hero behavior, SVG/icon rendering, resolved font family, FAQ/interactive states, keyboard focus/traversal, touch targets, text-spacing resilience, and horizontal overflow;
6. preserve global `Header01`, Plato routes/ownership, `/reg/`, and `page_on_front`; do not create a permanent Page 05 URL unless runtime authority already establishes one;
7. keep `ELEMENTOR_RUNTIME_STATUS = NOT_PROVEN` and the milestone non-PASS until the authentic runtime evidence actually supports those claims.

The unresolved KSH Kanoon Articles v0.4.1 current-contract real-host qualification remains valid project work, but it is explicitly deferred from immediate priority. Do not reopen or modify its architecture as a side effect of Page 05 unless Page 05 exposes a real regression caused by that module.

Repository/static/CI checks may prove authority-document consistency and existing deterministic contracts. They cannot by themselves upgrade Page 05 to runtime/browser/accessibility PASS.

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

## 14. Historical Elementor public-homepage qualification artifact

Fresh Owner-qualified presentation evidence established the observed stack used for the merged PR #7 homepage qualification work:

- WordPress 7.1.2 / PHP 8.3.33;
- Hello Elementor 3.5.1;
- Elementor 4.3.2;
- Elementor Pro 4.3.0;
- Persian site language `fa_IR`;
- existing global Theme Builder header `Header01` (template ID 341, general header condition);
- existing current front page is the Plato managers' portal (page ID 62, slug `plato-user-panel`, template `tpl-user-panel.php`).

The Owner decision separates the public homepage from the managers' portal. The Plato page remains independently reachable and must not be recreated, renamed, deleted, or absorbed into the public page.

The versioned PR #7 page-body artifact remains at:

`elementor/homepage/ksh-public-homepage-body-v1.json`

It is retained as **historical/technical qualification evidence only**. It is **not** the selected final homepage implementation method. The Owner will manually build the final public pages in Elementor while preserving the global Header01 and the relevant accepted product boundaries.

The artifact still documents a bounded authority-supported subset that existed at that checkpoint: public identity hero, confirmed manager portal access, and the accepted `[ksh_kanoon_articles]` placement. Its deterministic validator continues to prove only the artifact's repository contract; it does not dictate the final page composition and it does not authorize changing the live `page_on_front` assignment.

---

## 15. Public information architecture proposal and locked URL decisions

The authoritative **proposal for Owner review** is:

`docs/SITE_INFORMATION_ARCHITECTURE.md`

That document defines the proposed page/destination inventory, Persian naming, canonical URL structure, navigation model, local-vs-external-vs-portal ownership, indexability/XML-sitemap intent, URL-stability rules, and future short-link boundary. It does not implement WordPress pages, menus, redirects, short links, SRWF, Plato, or Elementor layouts.

Current Owner-locked IA rules that must be preserved regardless of proposal review:

- local WordPress slugs/internal paths use **English/ASCII**, **lowercase**, short/stable human-typable naming, with hyphens only when materially needed;
- the canonical public student-registration path is exactly **`/reg/`**;
- `/reg/` remains stable and independent from Gravity Forms IDs, workflow IDs, plugin names, or temporary page IDs;
- future short links are aliases/redirects to canonical destinations and must not become the canonical Information Architecture;
- the final public homepage/pages are Owner-built manually in Elementor; the merged PR #7 JSON is historical/technical evidence, not the selected final implementation method;
- the existing manager portal remains `/plato-user-panel/` and Plato remains the authentication/service owner unless a later explicit Owner decision changes the public routing contract.

All other page names, slugs, navigation placements, external-service shortcuts, privacy-page recommendation, and sitemap intent in `SITE_INFORMATION_ARCHITECTURE.md` remain **PROPOSED — OWNER REVIEW REQUIRED** until explicitly accepted.
