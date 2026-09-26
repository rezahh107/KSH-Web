# ADR-001 — Kanoon Article-List Metadata Mirror

**Status:** ACCEPTED / LOCKED  
**Decision family:** WordPress integration / background acquisition  
**Scope:** Homepage Kanoon article-list module

## Context

KSH-Web should show current useful links from `kanoon.ir` without becoming a duplicate content/news system.

A usable public native RSS feed for the target Kanoon content was not discovered during prior investigation. The live site exposes article-list content for Latest and a `پربازدید هفته` module, and real-time freshness is not required.

## Decision

Use a **small site-specific WordPress extractor** that periodically acquires the relevant Kanoon HTML, extracts only the required article-list metadata, validates it, and updates a local last-known-good snapshot.

Normal visitor rendering reads only local data.

### Target source direction

- Latest: `https://www.kanoon.ir/Article/Days`
- Weekly Popular: `https://www.kanoon.ir/`

### Normalized data intent

Small records only, conceptually:

```text
latest[]
  title
  url
  date/day-context when reliable

weekly_popular[]
  title
  url

per-list updated/status metadata
```

Exact storage schema is an implementation detail and should remain minimal.

## Locked behavior

- Target approximately 20 Latest links.
- Preserve Kanoon's own Weekly Popular ordering.
- Do not calculate popularity locally.
- Do not copy article bodies.
- Do not mirror images/media.
- Do not create remote-article Posts/CPTs merely for this feature.
- Do not use iframe/source-page embedding as the presentation model.
- Do not fetch Kanoon synchronously during ordinary visitor page loads.
- Approximately daily/nightly refresh is sufficient.
- Failed acquisition/parsing must preserve the previous valid snapshot.
- Latest and Weekly Popular must be independently validatable.
- Zero/malformed/ambiguous extraction must not replace valid prior data.

## Qualification gate before enabling writes/scheduling

The implementation must expose a **read-only Preview/Test Connection** on the real target WordPress host.

Before persistence/scheduling is enabled, that preview should establish:

- WordPress Core HTTP acquisition can obtain usable source HTML from the target host/network path;
- Latest records can be extracted from the bounded source;
- Weekly Popular can be distinguished from Monthly Popular using a defensible real DOM boundary;
- normalized title/URL/list identity are correct and source order is preserved;
- malformed/empty/ambiguous results can be detected.

A separate simulation lab is not a prerequisite for this feature because a simulation cannot prove the production host/IP/network path. Until the real-host preview runs, production-host acquisition remains `NOT_PROVEN`.

## Failure model

On timeout, HTTP error, missing selector/container, zero unexpected items, malformed URLs/titles, or ambiguity:

- reject the invalid candidate list;
- preserve the previous valid list;
- record/report the error in the implementation's smallest useful admin/diagnostic surface.

One healthy list may advance while the other retains its previous last-known-good data if validation is independent and snapshot merge semantics are safe.

## Rejected / unnecessary families for current scope

Not selected because they add dependencies or product scope without solving a current requirement:

- external Website-to-RSS SaaS;
- general-purpose scraping suite;
- external crawler/headless platform;
- browser automation;
- WordPress Post/CPT import pipeline;
- custom database table without demonstrated need;
- real-time polling;
- full-content mirroring.

## Consequences

### Positive

- low dependency surface;
- visitor availability is isolated from Kanoon availability;
- small maintenance surface when source markup changes;
- no recurring SaaS dependency required;
- KSH-Web controls its own presentation.

### Risk

The primary operational risk is source DOM change on `kanoon.ir`.

Containment is:

`strict validation + per-list last-known-good + visible preview/status + bounded parser repair`

not additional scraping infrastructure by default.

## Reopen condition

Do not reopen this ADR during ordinary implementation.

Reopen only if direct executed evidence shows the selected family cannot reliably acquire/distinguish the required data on the real target environment, or if the Owner materially changes the product requirement.
