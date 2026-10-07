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

## Qualification admission before enabling writes/scheduling

The implementation must expose a **read-only Preview/Test Connection** on the real target WordPress host and must enforce qualification as runtime admission, not merely as operational documentation.

The exact acquisition/parser contract has an explicit identity independent from the plugin version. The current identity is:

```text
kanoon-homepage-semantic-lists-v1
```

If a future material source/parser contract changes, that identity must change. Qualification from a different identity is stale and cannot authorize the new contract automatically.

### Source of truth

One bounded non-autoloaded WordPress option records only successful qualification evidence for one explicit acquisition-contract identity. A usable qualification record must contain the current contract id and a successful qualification timestamp. Historical plugin versions, Preview success from another contract, old Cron success, or user-submitted flags are not admission authority.

### Explicit Owner qualification action

Ordinary Preview remains read-only and never silently mutates qualification state.

A separate protected Owner action under WordPress Tools may qualify the current contract. That action must:

1. execute the exact current `Preview_Service` acquisition/parser checks;
2. require both Latest and Weekly Popular to be `success`, non-empty, and internally valid;
3. write the bounded current-contract qualification record only after those checks succeed;
4. verify qualification-state persistence before treating the contract as admitted;
5. permit scheduling only after the exact current contract is admitted.

Failed, partial, ambiguous, zero-item, unavailable, or state-write-failed qualification attempts must not create a success record.

### Writable admission boundary

For an unqualified current contract:

- ordinary read-only Preview remains available;
- public rendering continues reading existing local snapshots only;
- existing last-known-good article snapshots remain untouched;
- Manual and Cron refresh are blocked before acquisition and before snapshot/attempt/run-summary mutation;
- blocked execution does not fabricate attempt/run-summary success evidence;
- a new recurring event is not scheduled;
- an existing recurring event left by a previous plugin/acquisition contract is cleared by the schedule self-heal path;
- even if such a stale event callback is invoked before cleanup, the callback and canonical refresh boundary both fail closed.

This double boundary is intentional: schedule state is not trusted as qualification evidence, and a stale WordPress Cron event must never become a write bypass. WordPress itself treats a registered recurring event as a hook that can later invoke its callback; registration and execution are separate lifecycle facts. The plugin therefore guards both effective scheduling and the writable callback path.

### Operational execution-evidence provenance

Qualification authority and execution evidence are separate facts. A contract may be qualified without having executed through Manual/Cron yet, and historical execution under an older contract must not be projected as execution of the current one.

Newly persisted Manual/Cron run summaries therefore carry the exact `Source_Config::ACQUISITION_CONTRACT_ID` that produced them. Per-list attempts carry the same acquisition-contract identity because attempts participate in run correlation and observability checks.

The diagnostic support artifact classifies persisted run/attempt evidence as:

- `current_contract` — explicit identity exactly matches the current acquisition contract;
- `legacy_unknown_contract` — historical evidence has no acquisition-contract identity;
- `stale_contract` — evidence explicitly names a different acquisition contract.

Only an exact `current_contract` Cron run summary may satisfy the current-contract `cron_execution_observed` claim. Qualification by itself cannot upgrade legacy/stale execution evidence. Manual and Cron provenance remain independent.

Legacy and stale summaries/attempts are deliberately preserved and remain readable as historical support evidence. They are not deleted during upgrade and do not become current-contract proof merely because their trigger, timestamp, or run id is otherwise usable. Current-contract attempt/run correlation likewise requires current-contract provenance on the participating evidence.

The diagnostic remains read-only and exposes the current contract id, current qualification status when available, and the provenance of persisted Manual/Cron evidence without weakening its privacy exclusions.

### Historical qualification status

The original gate was executed by the Owner on the real KSH WordPress host using the merged v0.1.0 Preview implementation.

Observed at that historical execution:

- Latest: PASS, 20 valid records, HTTP 200 from the then-current `/Article/Days` implementation;
- Weekly Popular: PASS, 16 valid records, HTTP 200 from `https://www.kanoon.ir/`;
- WordPress version visibly observed: 7.1.2.

That evidence qualifies only the exact historical source/parser/runtime path that ran then. It does **not** qualify the later Owner-approved semantic change that moves Latest to the homepage `تازه‌ها` tab/list, and it does not prove future DOM/network stability.

After merged PR #4, the Owner also observed a v0.2.1 Cron-origin successful acquisition/persistence/diagnostic run. That remains historical runtime evidence for the architecture, not proof of the new homepage-Latest DOM binding. Because the historical summary predates acquisition-contract provenance, the current v0.4.1 diagnostic must retain it as `legacy_unknown_contract` evidence rather than treating it as current-contract Cron execution.

Therefore the current v0.4.1 release's homepage semantic contract remains **NOT_PROVEN on the real KSH host** until the Owner runs the explicit qualification action successfully for `kanoon-homepage-semantic-lists-v1` on that host.

## Failure model

On timeout, HTTP error, missing/duplicate semantic label, missing/colliding semantic target, zero unexpected items, malformed URLs/titles, or other bounded ambiguity:

- reject the invalid candidate list;
- preserve the previous valid list;
- record/report the error in the implementation's smallest useful admin/diagnostic surface;
- do not silently reinterpret `/Article/Days` as Latest.

After current-contract qualification, one healthy list may advance while the other retains its previous last-known-good data if validation is independent and snapshot merge semantics are safe.

Before current-contract qualification, neither Manual nor Cron refresh is admitted to writable acquisition/persistence at all.

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
- a shared-fetch/cache orchestration subsystem solely because both current lists use the same homepage URL;
- documentation-only qualification sequencing without runtime admission enforcement.

## Consequences

### Positive

- low dependency surface;
- visitor availability is isolated from Kanoon availability;
- small maintenance surface when source markup changes;
- no recurring SaaS dependency required;
- KSH-Web controls its own presentation;
- acquisition retains valid owned data independently of the public 15-item display cap;
- a changed acquisition contract cannot silently inherit old production write authority;
- a changed acquisition contract cannot silently inherit old Manual/Cron execution proof.

### Risk

The primary operational risk is source DOM change on `kanoon.ir`.

Containment is:

`explicit contract identity + real-host qualification admission + contract-bound execution evidence + strict semantic binding + per-list last-known-good + visible preview/status + bounded parser repair`

not additional scraping infrastructure by default.

## Reopen condition

Do not reopen this ADR during ordinary implementation.

Reopen only if direct executed evidence shows the selected family cannot reliably acquire/distinguish the required data on the real target environment, or if the Owner materially changes the product requirement.
