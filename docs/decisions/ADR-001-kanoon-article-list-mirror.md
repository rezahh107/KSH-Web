# ADR-001 — Kanoon Article-List Metadata Mirror

**Status:** ACCEPTED / LOCKED  
**Decision family:** WordPress integration / background acquisition  
**Scope:** Homepage Kanoon article-list module

## Context

KSH-Web should show current useful links from `kanoon.ir` without becoming a duplicate content/news system.

A usable public native RSS feed for the target Kanoon content was not discovered during prior investigation. The live site exposes semantic homepage lists for `تازه‌ها`, `پربازدید هفته`, and `پربازدید ماه`, and real-time freshness is not required.

## Decision

Use a **small site-specific WordPress extractor** that periodically acquires the relevant Kanoon HTML, extracts only the required article-list metadata, validates it, and updates a local last-known-good snapshot.

Normal visitor rendering reads only local data.

### Target source direction

Both owned lists are acquired from the Kanoon homepage:

- Latest / `تازه‌ها`: `https://www.kanoon.ir/`, bound through the semantic `تازه‌ها` tab/list relationship to its actual target container;
- Weekly Popular / `پربازدید هفته`: `https://www.kanoon.ir/`, bound through the existing semantic tab/target relationship with Monthly Popular as the sibling boundary.

`https://www.kanoon.ir/Article/Days` is **not** the public semantic source for Latest and must not be used as a silent fallback under the `تازه‌ها` label.

The two list outcomes remain independently acquired/validated even though their current URL is the same. A shared remote-fetch/cache subsystem is not required for this scale.

### Normalized data intent

Small records only, conceptually:

```text
latest[]
  title
  url
  date/day-context when reliably available

weekly_popular[]
  title
  url

per-list updated/status metadata
```

Exact storage schema is an implementation detail and should remain minimal. Existing `date_context` schema fields remain compatible; homepage Latest may legitimately persist an empty string when no reliable bounded date belongs to an article. Dates must not be guessed.

## Locked behavior

- Latest means the semantic `تازه‌ها` list on the Kanoon homepage, not `/Article/Days`.
- Preserve source ordering for both owned lists.
- The local snapshot may preserve the complete valid owned list; the public renderer displays at most the first **15** valid links per list.
- The 15-item rule is presentation-only and must not discard otherwise valid persisted records.
- Do not calculate popularity locally.
- Do not copy article bodies.
- Do not mirror images/media.
- Do not create remote-article Posts/CPTs merely for this feature.
- Do not use iframe/source-page embedding as the presentation model.
- Do not fetch Kanoon synchronously during ordinary visitor page loads.
- Approximately daily/nightly refresh is sufficient.
- Failed acquisition/parsing must preserve the previous valid snapshot.
- Latest and Weekly Popular must remain independently validatable.
- Zero/malformed/ambiguous extraction must not replace valid prior data.
- Public presentation must not expose `date_context`.
- Public presentation must not introduce nested list scrolling, forced equal panel heights, or title truncation solely to equalize geometry.

## Qualification gate before enabling writes/scheduling

The implementation must expose a **read-only Preview/Test Connection** on the real target WordPress host.

Before persistence/scheduling is enabled, that preview should establish:

- WordPress Core HTTP acquisition can obtain usable source HTML from the target host/network path;
- Latest records can be extracted from the bounded semantic homepage target;
- Weekly Popular can be distinguished from Monthly Popular using a defensible real DOM boundary;
- normalized title/URL/list identity are correct and source order is preserved;
- malformed/empty/ambiguous results can be detected.

A separate simulation lab is not a prerequisite because it cannot prove the production host/IP/network path.

### Historical qualification status

The original gate was executed by the Owner on the real KSH WordPress host using the merged v0.1.0 Preview implementation.

Observed at that historical execution:

- Latest: PASS, 20 valid records, HTTP 200 from the then-current `/Article/Days` implementation;
- Weekly Popular: PASS, 16 valid records, HTTP 200 from `https://www.kanoon.ir/`;
- WordPress version visibly observed: 7.1.2.

That evidence qualifies only the exact historical source/parser/runtime path that ran then. It does **not** qualify the later Owner-approved semantic change that moves Latest to the homepage `تازه‌ها` tab/list, and it does not prove future DOM/network stability.

After merged PR #4, the Owner also observed a v0.2.1 Cron-origin successful acquisition/persistence/diagnostic run. That remains historical runtime evidence for the architecture, not proof of the new homepage-Latest DOM binding.

The architectural qualification requirement remains part of this ADR. Any current-source observation after the semantic change is evidence for that exact observed page shape only.

## Failure model

On timeout, HTTP error, missing/duplicate semantic label, missing/colliding semantic target, zero unexpected items, malformed URLs/titles, or other bounded ambiguity:

- reject the invalid candidate list;
- preserve the previous valid list;
- record/report the error in the implementation's smallest useful admin/diagnostic surface;
- do not silently reinterpret `/Article/Days` as Latest.

One healthy list may advance while the other retains its previous last-known-good data if validation is independent and snapshot merge semantics are safe.

## Presentation ownership and typography

KSH owns the article module's component-level density and responsive presentation: font size, line height, weight, spacing, padding, RTL/grid behavior, focus visibility, and local visual treatment.

Font asset/family delivery belongs to the site's typography layer. In the Owner's current stack that companion is `rezahh107/Vazir`, whose canonical family is `Vazirmatn`. KSH must inherit the delivered site family rather than bundle fonts, define `@font-face`, invent a `Vazir` alias, or create a PHP/runtime dependency on the companion plugin.

## Rejected / unnecessary families for current scope

Not selected because they add dependencies or product scope without solving a current requirement:

- external Website-to-RSS SaaS;
- general-purpose scraping suite;
- external crawler/headless platform;
- browser automation;
- WordPress Post/CPT import pipeline;
- custom database table without demonstrated need;
- real-time polling;
- full-content mirroring;
- a shared-fetch/cache orchestration subsystem solely because both current lists use the same homepage URL.

## Consequences

### Positive

- low dependency surface;
- visitor availability is isolated from Kanoon availability;
- small maintenance surface when source markup changes;
- no recurring SaaS dependency required;
- KSH-Web controls its own presentation;
- acquisition retains valid owned data independently of the public 15-item display cap.

### Risk

The primary operational risk is source DOM change on `kanoon.ir`.

Containment is:

`strict semantic binding + per-list last-known-good + visible preview/status + bounded parser repair`

not additional scraping infrastructure by default.

## Reopen condition

Do not reopen this ADR during ordinary implementation.

Reopen only if direct executed evidence shows the selected family cannot reliably acquire/distinguish the required data on the real target environment, or if the Owner materially changes the product requirement.
