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

This qualifies the observed runtime path from the real KSH host through WordPress HTTP acquisition, current Kanoon HTML, parser/validation, and normalized results at that execution. It does not prove future DOM/network availability, persistence correctness, future scheduled execution, frontend rendering, or the exact production PHP version.

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

Current backend implementation includes read-only Preview, independent local last-known-good snapshots, bounded per-list attempt state, explicit Manual/Cron refresh attribution through one canonical refresh service, one approximately-daily native WP-Cron path, separate latest Manual/Cron run summaries, and a one-click read-only JSON diagnostic export from the existing Tools page. The frontend article module remains intentionally separate and not yet implemented.

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
- Owner-executed real-host Preview PASS for Latest and Weekly Popular on the observed KSH runtime path.

### Implemented backend stage

- read-only Preview/Test Connection;
- independent validated per-list snapshot storage using WordPress Options;
- bounded per-list latest-attempt status with explicit origin/run identity for new attempts and legacy `unknown` compatibility;
- explicit Manual and scheduled Cron entry paths that reuse the same canonical acquisition/validation/persistence implementation;
- separate non-autoloaded latest Manual and latest Cron run summaries, without unbounded history;
- one native daily WP-Cron hook with upgrade-safe schedule existence repair;
- one-click authenticated JSON diagnostic download containing only bounded plugin-owned/public metadata and safe WordPress/PHP runtime facts;
- deterministic separation between schedule registration and persisted Cron execution evidence;
- deactivation unscheduling without deleting valid snapshots or diagnostic run summaries.

### Not yet proven / not yet implemented

- actual future WP-Cron firing on the real KSH host after v0.2.1 is installed; this remains `NOT_PROVEN` until a real-host diagnostic report contains persisted `trigger=cron` run evidence;
- future source-DOM/network stability;
- final public/frontend article presentation;
- production deployment procedure beyond the Owner's normal plugin installation path.

Do not upgrade any of these to `PASS` from repository tests alone.

---

## 12. Near-term implementation sequence

The read-only real-host acquisition gate has passed, and the backend now includes validated local persistence, bounded native refresh scheduling, explicit refresh-origin observability, and the one-click diagnostic export required for operational qualification.

The Owner's normal post-merge/runtime workflow is:

1. install/update the plugin;
2. allow normal site operation / WP-Cron opportunity;
3. open the existing plugin Tools page;
4. click **دانلود گزارش JSON**;
5. provide that single JSON file to the Project Manager / LLM.

That report is the preferred support bundle. It must remain read-only and distinguish `event_registered=true` from `cron_execution_observed=true`. A registered event without persisted Cron-origin run evidence is not Cron PASS.

Do **not** proceed to the frontend article module based on repository tests alone; use the real-host diagnostic report for the remaining runtime qualification facts.

For the broader site, implementation should follow the accepted visual structure and actual WordPress environment rather than creating speculative infrastructure.

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
