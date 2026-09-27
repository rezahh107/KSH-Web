# KSH-Web — Site Information Architecture

**Status:** **PROPOSED — OWNER REVIEW REQUIRED**  
**Scope:** Public website page structure, destinations, naming, URL policy, navigation, indexing intent, and future alias boundaries.  
**Authority note:** Items explicitly marked **OWNER-LOCKED** are already decided. Everything else in this document is a proposal until the Owner accepts it.

This document defines **what destinations the KSH-Web site should expose**, not how Elementor should lay them out. The final public pages are Owner-built/manual in Elementor. It does not change the live WordPress front page, menus, Header01, Plato, SRWF, redirects, or short-link infrastructure.

---

## 0. Decision classes used here

- **OWNER-LOCKED** — already decided by the Owner; this proposal does not reopen it.
- **STANDARDS-DERIVED** — a recommendation shaped by current first-party Google, W3C/WAI, or WordPress guidance.
- **EXECUTOR PROPOSAL** — the smallest coherent product structure recommended from current repository/runtime evidence.

### Owner-locked URL rules

- WordPress page slugs/internal site paths use **English/ASCII**, **lowercase**, **short/stable human-typable words**, with hyphens only when materially needed.
- The canonical public student-registration path is exactly **`/reg/`**.
- `/reg/` remains the canonical public entry even if the underlying SRWF technical implementation changes.
- Future short links are **aliases/redirects to canonical destinations**; they are not canonical Information Architecture.
- No future short-link domain, namespace, software, analytics model, database, or redirect engine is selected by this document.

Google notes that audience-language URLs can be useful, but the Owner's explicit English/ASCII rule is authoritative for KSH-Web and therefore intentionally wins here.

---

# 1. Simple visual site tree

```text
KSH-Web
├── خانه  /                                      [local public page — PROPOSED final public homepage]
│   └── تازه‌های کانون                           [homepage module only; no local article archive]
├── درباره ما  /about/                           [local public page — PROPOSED]
├── تماس با ما  /contact/                        [local public page — PROPOSED]
├── ثبت‌نام  /reg/                               [SRWF workflow entry — OWNER-LOCKED URL]
├── خدمات کانون                                  [navigation group/action — NO WordPress landing page]
│   ├── صفحه شخصی دانش‌آموز                      [external: kanoon.ir]
│   ├── برنامه راهبردی                           [external: kanoon.ir]
│   └── نمونه سوال امتحانی                       [external: kanoon.ir]
├── ورود مدیران  /plato-user-panel/              [existing local portal; Plato-owned]
├── حریم خصوصی  /privacy/                        [utility page — PROPOSED, Owner/legal review]
└── ابزارها/فرم‌های داخلی کارکنان                [direct/private links — destinations NOT_PROVEN]
```

**Important:** `خدمات کانون` is proposed as a navigation grouping/control, **not** a local WordPress page. Creating a thin `/services/` landing page would add a click and duplicate authoritative external service logic without current product value.

---

# 2. Page / destination map

| Preferred Persian name | Canonical slug / URL | Type | Primary audience | Plain-language purpose | Logic/content owner | Suggested navigation placement | Indexability intent | XML sitemap intent | Current status | Priority |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| خانه | `/` | local page | everyone | Official public entry point for the Shiraz branch and its main destinations | KSH-Web / Owner manual Elementor content | Primary/header; logo also returns home | **Index** | **Include** | **PROPOSED** final public homepage; current live `page_on_front` is still the Plato portal | P0 |
| درباره ما | `/about/` | local page | prospective/current families and students | Durable introduction to the Shiraz branch; identity and branch-level context | KSH-Web content | Primary/header + footer | **Index** | **Include** | **PROPOSED** | P1 |
| تماس با ما | `/contact/` | local page | everyone needing branch contact/help | One stable destination for verified contact/location/help information | KSH-Web content | Primary/header + footer | **Index** | **Include** | **PROPOSED** | P1 |
| ثبت‌نام | `/reg/` | form/workflow | students and parents | Stable public entry into the student-registration journey | **SRWF** owns workflow/data; KSH-Web owns only the public entry/path and link placement | Important header CTA + homepage service/action shortcut + footer | **PROPOSED: Index** as a public stable entry unless SRWF later supplies a documented reason not to | **Include** if indexable | **LOCKED URL**; exact runtime binding of `/reg/` to SRWF is **NOT_PROVEN** in KSH-Web | P0 |
| ورود مدیران | `/plato-user-panel/` | local portal | managers/staff | Reach the existing manager authentication/service portal | **Plato User Panel** | Secondary/header utility; homepage service shortcut where useful; footer secondary | **Noindex intent** | **Exclude** | **EXISTING** path and logic owner | P0 retain |
| صفحه شخصی دانش‌آموز | `https://www.kanoon.ir/Account/Student` | external service | current Kanoon students | Reach Kanoon's authoritative student account/report-card area | `kanoon.ir` | `خدمات کانون` navigation group + homepage service shortcut + footer services | N/A on KSH-Web | N/A | **EXISTING external service**, verified 2026-09-27 | P1 |
| برنامه راهبردی | `https://www.kanoon.ir/Barname` | external service | students and parents | Reach Kanoon's current strategic-program downloads | `kanoon.ir` | `خدمات کانون` navigation group + homepage service shortcut + footer services | N/A on KSH-Web | N/A | **EXISTING external service**, verified 2026-09-27 | P1 |
| نمونه سوال امتحانی | `https://www.kanoon.ir/Public/ExamQuestions` | external service | students | Reach Kanoon's authoritative exam-question bank | `kanoon.ir` | `خدمات کانون` navigation group; homepage shortcut only if space/priority supports it | N/A on KSH-Web | N/A | **EXISTING external service**, verified 2026-09-27 | P2 |
| حریم خصوصی | `/privacy/` | utility/legal page | students, parents, general visitors | Explain privacy/data-use handling and point users to applicable registration/data notices without duplicating workflow logic | KSH-Web public notice; exact legal wording requires Owner/legal review; SRWF remains owner of registration data/workflow semantics | Footer; contextual link near registration where appropriate | **Index** unless later legal/SEO review decides otherwise | **Include** if published/indexable | **PROPOSED — Owner decision required** | P1 before broad registration promotion |
| فرم‌ها و ابزارهای داخلی کارکنان | **No route proposed** | local portal / direct-private link | staff only | Reach internal utilities only when concrete destinations are known | Relevant owning systems | Not public primary navigation; direct/private or manager/staff surface | **Noindex / access-controlled intent** | **Exclude** | **NOT_PROVEN** destinations | DEFERRED |

### About: separate page or homepage section?

**EXECUTOR PROPOSAL:** keep a real **`درباره ما → /about/`** page. The homepage can contain a short introduction, but the branch introduction is durable, independently linkable information and should not be trapped inside a homepage section.

### Contact: separate page or homepage section?

**EXECUTOR PROPOSAL:** keep a real **`تماس با ما → /contact/`** page. Contact/help is a distinct user goal and benefits from one stable inbound destination. Do not invent address, phone, staff, hours, maps, or statistics until verified content is supplied.

### Services page?

**EXECUTOR PROPOSAL:** **do not create `/services/` now.** Current useful services are direct destinations owned by SRWF, Plato, or Kanoon. A local wrapper page would mostly duplicate links already reachable from the homepage/header/footer. Reconsider only if KSH later owns enough service-specific content to justify a real page.

---

# 3. Registration entry — fixed boundary

**OWNER-LOCKED:**

- Visible concept: **ثبت‌نام**
- Canonical public path: **`/reg/`**
- Workflow/data owner: **SRWF**

The public URL must remain independent from Gravity Forms IDs, Gravity Flow workflow IDs, plugin names, temporary WordPress page IDs, or implementation-specific routes.

Current KSH-Web evidence does **not** prove the eventual runtime binding method behind `/reg/`. Therefore only that binding is **NOT_PROVEN**; the path itself is not open for redesign.

The official Kanoon site currently exposes its own separate pre-registration flow at `https://www.kanoon.ir/Register/PreRegister`. That verified external service must **not** replace or share the same KSH label as `/reg/` unless the Owner later defines a clearly distinct product use case. Two visually equivalent “ثبت‌نام” actions with different owners would be confusing.

---

# 4. Manager access

Current proven KSH endpoint:

- User-facing label: **ورود مدیران**
- Current path: **`/plato-user-panel/`**
- Logic owner: **Plato User Panel**

**EXECUTOR PROPOSAL:** keep the existing path as-is for now and solve clarity at the UI-label level. The public navigation does not need to expose the word “Plato”; `ورود مدیران` describes the user goal while preserving the proven portal endpoint.

A new `/managers/` alias would be easier to type, but current users can reach the portal from site navigation and a future short-link layer is already planned for cases where memorability/typing matters. Adding another alias today creates migration/redirect state without a demonstrated need. **No manager alias is recommended in this IA revision.**

The portal is not a public search landing page; the intended policy is **noindex + excluded from XML sitemap**, without duplicating or modifying Plato authentication.

---

# 5. Student / Kanoon service destinations

Use direct authoritative destinations when KSH does not own the service logic.

## Admitted external shortcuts

1. **صفحه شخصی دانش‌آموز** → `https://www.kanoon.ir/Account/Student`
2. **برنامه راهبردی** → `https://www.kanoon.ir/Barname`
3. **نمونه سوال امتحانی** → `https://www.kanoon.ir/Public/ExamQuestions`

These are direct external service links, not KSH content pages. KSH should use clear Persian link text describing what the user will get; it should not create local wrapper pages merely to keep the browser on the Shiraz domain.

## Not admitted as a current KSH destination

- Kanoon's separate online/pre-registration routes: official URLs exist, but their relationship to the Owner-locked SRWF `/reg/` journey is not defined. **DEFERRED / product role NOT_PROVEN.**
- Other Kanoon sections (books, rankings, scholarship, city pages, etc.): they may be legitimate Kanoon services, but no current KSH product requirement makes all of them part of the primary IA. Add only when a concrete branch/user need is approved.

---

# 6. Articles

The existing **Latest + Weekly Popular** KSH Kanoon Articles component belongs on the **homepage only** in the current architecture.

- Do **not** create a local `/articles/`, `/news/`, or duplicate archive.
- Individual article items continue to link to the canonical `kanoon.ir` article URLs.
- `پربازدید ماه` remains outside current scope.
- The article component is a homepage content module, not a new site hierarchy branch.

This preserves the accepted ADR: KSH-Web surfaces useful links without becoming a duplicated news/content portal.

---

# 7. Staff / internal paths

Repository authority proves the manager portal, but it does **not** currently prove a complete set of staff/internal form destinations.

Policy:

- do not invent slugs for unknown internal utilities;
- do not place every operational form in public primary navigation;
- known internal utilities should normally be reached through an owning portal, authenticated surface, or direct/private link;
- when a concrete route is later supplied, record its owner, audience, access policy, and whether it belongs under manager/staff navigation.

Current classification: **NOT_PROVEN / DEFERRED**, except the existing Plato manager portal.

---

# 8. Utility / legal surfaces

## Privacy / data-use notice

**EXECUTOR PROPOSAL:** add **`حریم خصوصی → /privacy/`** as a small public utility page before registration is broadly promoted.

Reason: the product includes student registration and therefore has a real user need for a stable place to understand data-use/privacy information and reach any more specific SRWF notice. This is a product/usability recommendation, **not a legal conclusion**. Exact legal obligations, controller language, retention claims, and final copy are not established here and require Owner/legal review.

## Accessibility/help page

**Do not add a separate accessibility/help page now.** For this small site, the Contact page can be the stable human-help destination. Reconsider a dedicated page only if there is actual accessibility/help content that cannot fit naturally there.

---

# 9. Navigation model

## 9.1 Primary/header navigation

Keep it small and goal-oriented:

1. **خانه** → `/`
2. **درباره ما** → `/about/`
3. **خدمات کانون** → navigation group/control, not a WordPress page
   - صفحه شخصی دانش‌آموز
   - برنامه راهبردی
   - نمونه سوال امتحانی
4. **تماس با ما** → `/contact/`

The repeated header order should remain consistent across public pages.

## 9.2 Important CTA/action links

- **ثبت‌نام** → `/reg/` — primary public CTA.
- **ورود مدیران** → `/plato-user-panel/` — secondary utility action, not equivalent visual priority to public registration.

`صفحه شخصی دانش‌آموز` is an important service shortcut, but it should remain inside the service grouping/homepage shortcuts rather than compete with `/reg/` as a global primary CTA.

## 9.3 Footer navigation

Suggested footer groups:

- **کانون شیراز:** خانه، درباره ما، تماس با ما
- **دسترسی سریع:** ثبت‌نام، ورود مدیران
- **خدمات کانون:** صفحه شخصی دانش‌آموز، برنامه راهبردی، نمونه سوال امتحانی
- **اطلاعات:** حریم خصوصی (if approved/published)

Do not add a duplicate article archive link because no such local archive is proposed.

## 9.4 Manager/internal navigation

Manager/internal destinations should be visually and semantically separated from general public navigation. `ورود مدیران` may appear as a utility action in Header01 and footer, while staff-only tools stay inside their owning/authenticated surfaces or direct/private links.

---

# 10. Homepage relationship

The Owner will build the final homepage manually in Elementor. The IA only requires the homepage to expose the following destinations/content responsibilities:

- clear site/branch identity;
- **ثبت‌نام → `/reg/`** as the main public action;
- **ورود مدیران → `/plato-user-panel/`** as a secondary utility action;
- shortcuts to the admitted Kanoon services;
- a concise About introduction that links to `/about/`;
- Contact access to `/contact/`;
- the accepted **Latest + Weekly Popular** article component;
- privacy/data-use access where context requires it after `/privacy/` is approved.

This document intentionally does **not** prescribe Elementor section layout, widgets, spacing, or visual implementation.

The merged PR #7 homepage JSON remains historical/technical evidence and a bounded reference artifact; it is **not** the selected final homepage implementation method.

---

# 11. Human Information Architecture vs search-engine XML sitemap

These are different things:

- **Human IA** describes what users can reach and how destinations relate.
- **XML sitemap** tells search engines which KSH-hosted URLs are intended as important crawl/index candidates. It is not a menu and should not contain external services or redirect aliases.

## Proposed XML sitemap inclusion policy

| Destination class | XML sitemap intent |
| --- | --- |
| Public, canonical, indexable local pages (`/`, `/about/`, `/contact/`, `/reg/`) | **Include** |
| `/privacy/` if approved and published/indexable | **Include** |
| Manager/login portals such as `/plato-user-panel/` | **Exclude** |
| Staff/private/internal utilities | **Exclude** |
| Redirects and future short-link aliases | **Exclude** |
| External Kanoon services/articles | **Not applicable** — they belong to the external site's sitemap, not KSH-Web |
| Nonexistent/thin local wrapper pages | Do not create merely to populate a sitemap |

Actual WordPress/XML-sitemap implementation is outside this task. This table records intent only.

---

# 12. URL stability and redirect policy

1. Treat canonical slugs as long-lived product identifiers; change them rarely.
2. Keep internal links pointed directly at the canonical destination, not at an alias.
3. If a published canonical URL must change, map the old URL to the corresponding new destination with an appropriate **permanent server-side redirect** (normally 301/308), update internal links, and avoid redirect chains.
4. Do not delete an established useful URL casually or redirect unrelated old URLs to the homepage.
5. Do not reuse a former canonical path for unrelated content.
6. Redirect/alias URLs are transport conveniences, not content pages and not XML-sitemap entries.
7. External-service aliases must resolve to the authoritative external destination rather than pretending the service is locally owned.

No redirect implementation is created by this proposal.

---

# 13. Short-link readiness

Future short links may support SMS, QR codes, print, campaigns, or verbal sharing. To preserve that option:

- keep each real destination's canonical URL stable;
- keep alias identity separate from canonical page identity;
- allow a future alias to point either to a KSH canonical page or to an approved external authoritative service;
- do not use aliases in canonical tags, XML sitemaps, or the site's normal internal-link graph when the canonical destination can be linked directly;
- do not make campaign/short-link naming part of page hierarchy;
- do not assume any future domain/subdomain/namespace or shortening software here.

**`/reg/` is already the canonical registration URL, not a temporary short link.**

---

# 14. Standards research and concrete influence

Research date: **2026-09-27**. Only guidance that materially changes this IA is retained.

## Google Search Central

- **URL Structure Best Practices** — https://developers.google.com/search/docs/crawling-indexing/url-structure  
  Influence: keep URLs simple/readable, avoid IDs/implementation details, use hyphens when a separator is needed, and avoid needless parameterized/nested forms. Google's audience-language suggestion is acknowledged, but the Owner's English/ASCII lock takes precedence.
- **SEO Starter Guide / Organize your site** — https://developers.google.com/search/docs/fundamentals/seo-starter-guide  
  Influence: use a small logical structure and link important destinations directly rather than manufacturing hierarchy.
- **SEO Link Best Practices** — https://developers.google.com/search/docs/crawling-indexing/links-crawlable  
  Influence: use descriptive Persian link/CTA labels such as `ثبت‌نام`, `ورود مدیران`, and `برنامه راهبردی`, not plugin/system names.
- **Sitemaps overview** — https://developers.google.com/search/docs/crawling-indexing/sitemaps/overview  
  Influence: XML sitemap is for important KSH-hosted canonical pages; external URLs, aliases, portals, and private utilities are not sitemap content.
- **Redirects and Google Search** — https://developers.google.com/search/docs/crawling-indexing/301-redirects  
  Influence: preserve established URLs; if a canonical URL changes, prefer a permanent server-side redirect and avoid chains.

## W3C / WAI

- **WCAG 2.2 — Link Purpose (In Context)** — https://www.w3.org/WAI/WCAG22/Understanding/link-purpose-in-context.html  
  Influence: navigation/link labels describe the user goal/destination rather than internal technology.
- **WCAG — Consistent Navigation** — https://www.w3.org/WAI/WCAG21/Understanding/consistent-navigation.html  
  Influence: repeated header navigation keeps the same relative order across public pages.
- **WCAG 2.2 — Multiple Ways** — https://www.w3.org/WAI/WCAG22/Understanding/multiple-ways.html  
  Influence: important local pages are reachable through more than one appropriate path (for example header/homepage/footer) without needing a large sitemap-style menu.

## WordPress official documentation

- **Create Pages** — https://wordpress.org/documentation/article/create-pages/  
  Influence: About/Contact/Privacy fit WordPress Pages because they are durable, non-chronological content.
- **Customize Permalinks** — https://wordpress.org/documentation/article/customize-permalinks/  
  Influence: treat permalinks as stable public addresses and avoid unnecessary later changes.
- **Page/Post Settings — Slug** — https://wordpress.org/documentation/article/page-post-settings-sidebar/  
  Influence: set the required English slug explicitly rather than accepting an automatically generated Persian title slug.

---

# 15. NOT_PROVEN / deferred destination inventory

The following must not be invented during manual page construction:

1. **`/reg/` runtime binding method** — canonical path is locked; exact WordPress/SRWF binding is not yet proven in KSH-Web.
2. **Staff/internal form destinations beyond Plato** — no authoritative route inventory exists in the repository.
3. **Exact contact/location data** — structure is proposed, but address, phones, hours, maps, staff names, and statistics are not supplied by this IA.
4. **Any distinct use for Kanoon's own remote pre-registration route** — exists externally, but its KSH role relative to SRWF is not approved.
5. **Privacy/legal copy and exact legal obligations** — page is recommended; claims require Owner/legal review.
6. **Future short-link implementation** — capability boundary only; no technical solution selected.
7. **Live XML-sitemap/noindex configuration** — this document records intent, not current production state.

---

# 16. Owner Decision Summary

## Already locked

- Local WordPress slugs/internal paths use English/ASCII, lowercase, short/stable human-typable naming.
- Hyphens are used only when materially needed.
- Student registration canonical path is exactly **`/reg/`**.
- `/reg/` remains stable regardless of underlying SRWF implementation details.
- Future short links remain aliases/redirects; they do not become canonical IA.
- KSH-Web must not duplicate SRWF or Plato workflow/authentication logic.

## Recommended for approval

1. **Top-level local public page inventory:**
   - خانه → `/`
   - درباره ما → `/about/`
   - تماس با ما → `/contact/`
   - ثبت‌نام → `/reg/` (already locked)
   - حریم خصوصی → `/privacy/` (utility; Owner/legal review)
2. **No standalone `/services/` page**; use a navigation grouping plus direct authoritative service links.
3. **Primary navigation:** خانه، درباره ما، خدمات کانون، تماس با ما.
4. **Primary public CTA:** ثبت‌نام → `/reg/`.
5. **Secondary utility action:** ورود مدیران → keep existing `/plato-user-panel/`; no new manager alias now.
6. **Admitted Kanoon shortcuts:** صفحه شخصی دانش‌آموز، برنامه راهبردی، نمونه سوال امتحانی using the verified external URLs above.
7. **Articles stay homepage-only** as Latest + Weekly Popular; no local news/article archive.
8. **Privacy page recommendation:** approve `/privacy/` as the stable public data-use/privacy notice location; final legal wording remains separate.
9. **Search intent:** public canonical local pages in XML sitemap; portals/private utilities/redirect aliases excluded; external services not applicable.

Once these material decisions are approved, the Owner can build the final Elementor pages and navigation against one stable reference without coupling public URLs to plugins, page IDs, or temporary campaign aliases.
