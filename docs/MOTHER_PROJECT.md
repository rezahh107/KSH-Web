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

### Qualification strategy

A separate simulation laboratory is **not required as a prerequisite** for this feature.

The implementation should instead provide a **read-only Preview/Test Connection qualification path on the real target WordPress host before writes/scheduling are enabled**. That preview should prove usable remote acquisition and the exact parsing boundary for Latest and Weekly Popular on the actual host/network path.

Until that real-host preview executes successfully, production-host acquisition and exact source-DOM behavior remain `NOT_PROVEN`.

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

At repository-foundation time, the final decomposition of theme/plugin/source directories is not yet globally frozen. Do not invent a full code architecture in documentation before implementation work establishes it.

One exception is already selected: the Kanoon article-list acquisition should live as a **small site-specific WordPress component/plugin-level responsibility**, independent enough from presentation that source parsing/sync can be maintained without coupling the visitor UI to remote availability.

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

This is only the foundation-stage contract. As real implementation code is introduced, extend the same canonical verification path with applicable build/lint/static-analysis/test/runtime checks.

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
- local last-known-good snapshot;
- no visitor-time remote dependency;
- small custom WordPress extractor family;
- real-host read-only preview gate before writes/scheduling.

### Not yet proven / not yet implemented

- exact production theme/plugin directory architecture beyond the locked article-extractor responsibility;
- exact raw DOM selector used by the final extractor at implementation time;
- production-host outbound acquisition until read-only preview executes on the target host;
- final page copy/content details;
- full implementation build/test/runtime stack;
- production deployment procedure.

Do not upgrade any of these to `PASS` from documentation alone.

---

## 12. Near-term implementation sequence

The next implementation phase should preserve the frozen decisions and proceed in small verifiable work units. For the article module, first implement the read-only acquisition/preview qualification on the real host, then enable validated local persistence/scheduling, and finally wire the local data into the responsive presentation layer.

For the broader site, implementation should follow the accepted visual structure and actual WordPress environment rather than creating speculative infrastructure.

---

## 13. Non-goals for repository foundation

This foundation intentionally does not add:

- a license decision;
- CODEOWNERS for a single-owner topology;
- issue-form bureaucracy;
- release machinery before a release model exists;
- Git LFS for the current small reference asset;
- speculative dependency/security automation without a real dependency stack;
- placeholder build/test commands that do not yet exist.

These may become applicable later if the repository reality changes.
