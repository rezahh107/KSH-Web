# Repository Foundation Record

## Provenance

This repository foundation was prepared by applying the current Repository Foundation domain from:

`rezahh107/Personal-Preference-Decision-Model`

Inspected authority snapshot:

`main@baad152c841713c703f0f56ae60eeff120c6ef50`

Key source modules consulted:

- `knowledge/current/domains/repository-foundation/INDEX.md`
- `principles-and-boundaries.md`
- `foundation-standard.md`
- `engineering-feedback-loop.md`
- `github-governance-and-security.md`
- `ai-agent-readiness.md`
- `application-and-validation.md`
- `knowledge/current/defaults/DEF-REPO-01.md`
- `knowledge/current/domains/software-delivery.md`

The standard was adapted to this repository; its file tree was not copied.

## Target inspected

Initial target state:

`rezahh107/KSH-Web@9e768c0111d709300c7c24859bd06330acd68762`

The repository initially contained only:

- `README.md`
- `.gitattributes`

## Foundation intent

North Star:

`SELF-EXPLAINING + SELF-VALIDATING`

Applied procedure:

`Inspect → Applicability → Smallest Sufficient Delta → Validate`

## Applicability record

| Capability | Classification | Target decision | Result in this foundation |
|---|---|---|---|
| Project entrypoint / README | REQUIRED | APPLICABLE | Expanded with project pointers, status, and verification entrypoint. |
| Ignore policy | CONDITIONAL | APPLICABLE | Added minimal secret/runtime/editor ignore rules. |
| `.gitattributes` | CONDITIONAL | APPLICABLE | Preserved text normalization and added binary design-asset handling. |
| `.editorconfig` | RECOMMENDED | APPLICABLE | Added basic UTF-8/LF/editor consistency. |
| Scope/charter | RECOMMENDED | APPLICABLE | Added `docs/MOTHER_PROJECT.md`. |
| Architecture record | CONDITIONAL | APPLICABLE | Added ADR for the already-selected Kanoon article-list integration. |
| Setup/build/lint/test docs | REQUIRED when applicable | PARTIALLY APPLICABLE | No production code/toolchain exists yet; no fake build/lint/test commands were created. |
| Coherent verification contract | REQUIRED | APPLICABLE | Added `scripts/verify-foundation.sh`. |
| CI | CONDITIONAL | APPLICABLE | Added a minimal read-only workflow reusing the same foundation verification script. |
| Cross-agent instructions | CONDITIONAL | APPLICABLE | Added concise root `AGENTS.md` because AI-assisted engineering is an explicit use case. |
| UI/design authority artifact | CONDITIONAL | APPLICABLE | Added a repository-owned responsive reference plus interpretation contract. |
| PR integration boundary | CONDITIONAL | APPLICABLE for bootstrap | Foundation is delivered through a reviewable branch/PR rather than silently replacing `main`. |
| CODEOWNERS | CONDITIONAL | NOT_APPLICABLE now | Single-owner/simple topology; no concrete failure solved. |
| Issue forms/templates | CONDITIONAL | NOT_APPLICABLE now | No issue-intake volume/problem justifies them. |
| Git LFS | CONDITIONAL | NOT_APPLICABLE now | Current design asset is small after repository-friendly WebP export. |
| Dependency update/scanning automation | CONDITIONAL | NOT_APPLICABLE yet | No managed production dependency stack has been established. |
| Release/versioning machinery | CONDITIONAL | NOT_APPLICABLE yet | No release/distribution contract exists yet. |
| License | CONDITIONAL | OWNER_DECISION_REQUIRED | Intentionally not invented. |
| Branch/ruleset protection | CONDITIONAL | NOT_INSPECTED / not changed | Requires a separate platform-governance decision; workflow presence is not enforcement. |

## Verification boundary

Current canonical command:

```bash
bash scripts/verify-foundation.sh
```

The check validates:

- required foundation documents exist and are non-empty;
- the imported design reference matches its recorded SHA-256;
- key README/AGENTS authority pointers exist;
- obvious secret/runtime files are not tracked.

The accompanying GitHub Actions workflow executes the same contract on pull requests and pushes to `main`.

### Claim ceiling

A passing foundation check proves only the checked repository-foundation contract for that exact Head. It does not prove:

- WordPress runtime behavior;
- source-site connectivity;
- article extraction correctness;
- production deployment correctness;
- accessibility/performance/security properties not exercised by a real check.

Those checks must be added when their implementation exists.

## Future adaptation rule

When real code/tooling enters the repository:

1. inspect the actual stack;
2. preserve healthy mechanisms;
3. extend the canonical verification contract with applicable build/lint/static-analysis/test/runtime checks;
4. update `AGENTS.md` to point to proven commands;
5. do not create parallel human/agent/CI verification semantics when one underlying contract can be shared.
