#!/usr/bin/env bash
set -euo pipefail

repo_root="${1:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"
cd "$repo_root"

fail() {
  echo "CURRENT_AUTHORITY_VERIFY_FAIL: $*" >&2
  exit 1
}

require_file() {
  local path="$1"
  [[ -s "$path" ]] || fail "required authority file missing or empty: $path"
}

require_line() {
  local path="$1"
  local expected="$2"
  grep -Fxq -- "$expected" "$path" || fail "missing exact authority line in $path: $expected"
}

require_contains() {
  local path="$1"
  local expected="$2"
  grep -Fq -- "$expected" "$path" || fail "missing required authority text in $path: $expected"
}

reject_pattern() {
  local path="$1"
  local pattern="$2"
  local label="$3"
  if grep -Eq -- "$pattern" "$path"; then
    fail "$label in $path"
  fi
}

assert_only_token() {
  local path="$1"
  local regex="$2"
  local expected="$3"
  local label="$4"
  local matches=()
  mapfile -t matches < <(grep -Eo -- "$regex" "$path" || true)
  (("${#matches[@]}" > 0)) || fail "missing $label token in $path"
  local value
  for value in "${matches[@]}"; do
    [[ "$value" == "$expected" ]] || fail "conflicting $label in $path: expected '$expected', found '$value'"
  done
}

authority_files=(
  "docs/MOTHER_PROJECT.md"
  "README.md"
  "AGENTS.md"
  "docs/design/PAGE05_PRODUCTION_REFERENCE.md"
  "docs/design/UI_REFERENCE.md"
)

for path in "${authority_files[@]}" "docs/decisions/ADR-001-kanoon-article-list-mirror.md"; do
  require_file "$path"
done

canonical="docs/design/PAGE05_PRODUCTION_REFERENCE.md"

# Exact locked Page 05 repository-side identity contract.
require_line "$canonical" '- Nine-Page design package: `KSH_NINE_PAGE_DESIGN_SYSTEM_v1.0.1_COMPLETE.zip`'
require_line "$canonical" '- `DESIGN_SYSTEM_STATUS = COMPLETE`'
require_line "$canonical" '- Immediate milestone: `PAGE_05_ELEMENTOR_PRODUCTION_REFERENCE`'
require_line "$canonical" '- `ELEMENTOR_RUNTIME_STATUS = NOT_PROVEN`'
require_line "$canonical" 'Artifact: `KSH_PAGE05_MASTER_REFERENCE_v1.1`'
require_line "$canonical" 'Status: **OWNER APPROVED / CANONICAL / LOCKED**'
require_line "$canonical" '- file: `KSH_PAGE05_MASTER_DESKTOP_v1.1.png`'
require_line "$canonical" '- dimensions: `1440 × 3515 px`'
require_line "$canonical" '- SHA-256: `2b2a783fd98b68ec97867f7a686cfcc79993c7ee3e3bf60aff77a965f8b81597`'
require_line "$canonical" '- file: `KSH_PAGE05_MASTER_MOBILE_RESPONSIVE_v1.1.png`'
require_line "$canonical" '- dimensions: `390 × 3904 px`'
require_line "$canonical" '- SHA-256: `8877db443487d5d9e0f5a293c713e76a3397f5bda5c03f974174f612a6f8ab37`'
require_line "$canonical" 'Artifact: `KSH_PAGE05_HERO_REFERENCE_v1.0`'
require_line "$canonical" 'Status: `PAGE_05_HERO_ASSET_STATUS = VERIFIED / CANONICAL`'
require_line "$canonical" '- `KSH_PAGE05_HERO_SOURCE_v1.0.png`'
require_line "$canonical" '- `1536 × 1024 px`'
require_line "$canonical" '- SHA-256: `d4977852348e3f49c9c5b8df8ccd59b651b485eb90b2820524830d045b701a13`'
require_line "$canonical" '- `KSH_PAGE05_HERO_DESKTOP_v1.0.png`'
require_line "$canonical" '- `1495 × 1024 px`'
require_line "$canonical" '- SHA-256: `94e55bfc426bff42f525f0c1ec685ce9e61fffdcfee459cbb35eccd51f6e6973`'
require_line "$canonical" '- `KSH_PAGE05_HERO_MOBILE_v1.0.png`'
require_line "$canonical" '- `1536 × 914 px`'
require_line "$canonical" '- SHA-256: `8bfa78723bd42770560be17707bfa5fe1323227bb8115500f42dad334100682f`'
require_line "$canonical" '`8be5171342f3c7024b0f173bb152a9fcc94f2e1a58967d15d05d3b5a1b1234ba`'
require_line "$canonical" '`ksh-icon-system-production-candidate-v1.0.zip`'
require_contains "$canonical" 'Repository verification does not download or re-hash the Google Drive binary bytes.'

# Reject contradictions even when the correct token is still present elsewhere.
for path in "${authority_files[@]}"; do
  assert_only_token "$path" 'ELEMENTOR_RUNTIME_STATUS[[:space:]]*=[[:space:]]*[A-Z_]+' 'ELEMENTOR_RUNTIME_STATUS = NOT_PROVEN' 'Elementor runtime status'
done

for path in "docs/MOTHER_PROJECT.md" "AGENTS.md" "docs/design/PAGE05_PRODUCTION_REFERENCE.md" "docs/design/UI_REFERENCE.md"; do
  assert_only_token "$path" 'DESIGN_SYSTEM_STATUS[[:space:]]*=[[:space:]]*[A-Z_]+' 'DESIGN_SYSTEM_STATUS = COMPLETE' 'design-system status'
done

for path in "${authority_files[@]}"; do
  assert_only_token "$path" 'KSH_PAGE05_MASTER_REFERENCE_v[0-9]+(\.[0-9]+)*' 'KSH_PAGE05_MASTER_REFERENCE_v1.1' 'Page 05 Master identity'
  assert_only_token "$path" 'KSH_PAGE05_HERO_REFERENCE_v[0-9]+(\.[0-9]+)*' 'KSH_PAGE05_HERO_REFERENCE_v1.0' 'Page 05 Hero identity'
done

for path in "docs/MOTHER_PROJECT.md" "README.md" "AGENTS.md" "docs/design/PAGE05_PRODUCTION_REFERENCE.md"; do
  assert_only_token "$path" 'PAGE_05_ELEMENTOR_[A-Z_]+' 'PAGE_05_ELEMENTOR_PRODUCTION_REFERENCE' 'Page 05 milestone'
done

# Required repeated current-state summaries.
require_contains "docs/MOTHER_PROJECT.md" 'KSH Nine-Page Guidance Design System v1.0.1 is `COMPLETE / LOCKED`'
require_contains "docs/MOTHER_PROJECT.md" 'Page 05 Master v1.1 is the current Owner-approved canonical implementation reference'
require_contains "docs/MOTHER_PROJECT.md" 'Page 05 Hero v1.0 is `VERIFIED / CANONICAL`'
require_contains "docs/MOTHER_PROJECT.md" '`PAGE_05_ELEMENTOR_PRODUCTION_REFERENCE` is the immediate project milestone'
require_contains "README.md" '`KSH_NINE_PAGE_DESIGN_SYSTEM_v1.0.1_COMPLETE.zip` is complete/locked'
require_contains "README.md" '`KSH_PAGE05_MASTER_REFERENCE_v1.1` is the current Owner-approved canonical Page 05 visual reference'
require_contains "README.md" '`KSH_PAGE05_HERO_REFERENCE_v1.0` is the canonical Hero asset family'
require_contains "README.md" '`PAGE_05_ELEMENTOR_PRODUCTION_REFERENCE` is the immediate milestone'
require_contains "AGENTS.md" 'Page 05 Master v1.1 and Hero v1.0 are the current locked Page 05 implementation authority'
require_contains "AGENTS.md" '`PAGE_05_ELEMENTOR_PRODUCTION_REFERENCE` is the immediate milestone'
require_contains "docs/design/UI_REFERENCE.md" 'KSH_NINE_PAGE_DESIGN_SYSTEM_v1.0.1_COMPLETE.zip'
require_contains "docs/design/UI_REFERENCE.md" 'KSH_PAGE05_MASTER_REFERENCE_v1.1'
require_contains "docs/design/UI_REFERENCE.md" 'KSH_PAGE05_HERO_REFERENCE_v1.0'
require_contains "docs/design/UI_REFERENCE.md" 'repository verification does not download or re-hash the Drive binary bytes'

# Current KSH Kanoon Articles release/qualification guidance must be v0.4.1.
require_contains "README.md" 'Current development version: **0.4.1**.'
require_contains "README.md" 'When the current v0.4.1 article qualification work is resumed on KSH:'
require_contains "docs/MOTHER_PROJECT.md" 'The current KSH Kanoon Articles release is v0.4.1.'
require_contains "AGENTS.md" 'current v0.4.1 real-host acquisition-contract qualification remains `NOT_PROVEN`'
require_contains "docs/design/PAGE05_PRODUCTION_REFERENCE.md" 'KSH Kanoon Articles v0.4.1 current-contract real-host qualification remains a real unresolved project gap'
require_contains "docs/decisions/ADR-001-kanoon-article-list-mirror.md" "current v0.4.1 release's homepage semantic contract remains **NOT_PROVEN on the real KSH host**"

stale_current_patterns=(
  'current v0\.4\.0 identity'
  'repaired v0\.4\.0 qualification action'
  'through the repaired v0\.4\.0 Owner action'
  'real-host writable Manual/Cron execution for the v0\.4\.0 contract'
  'real-host/browser visual acceptance of v0\.4\.0'
  'The exact v0\.4\.0 contract'
  'Manual/Cron writable acquisition for the v0\.4\.0 contract'
  'v0\.4\.0 real-host acquisition-contract qualification remains'
  'Therefore the v0\.4\.0 homepage semantic contract remains'
  'a current v0\.4\.0 diagnostic'
)

for path in "docs/MOTHER_PROJECT.md" "README.md" "AGENTS.md" "docs/decisions/ADR-001-kanoon-article-list-mirror.md"; do
  for pattern in "${stale_current_patterns[@]}"; do
    reject_pattern "$path" "$pattern" "stale current/future v0.4.0 authority"
  done
done

# Genuine historical v0.4.0 evidence/implementation descriptions remain admitted.
require_contains "docs/MOTHER_PROJECT.md" '- v0.4.0 Latest parser binding to the homepage `تازه‌ها` semantic target'
require_contains "README.md" 'it does **not** qualify the v0.4.0 semantic change that now binds Latest to the homepage `تازه‌ها` list'
require_contains "README.md" 'the repaired v0.4.0 diagnostic classifies the surviving summary as `legacy_unknown_contract`;'
require_contains "AGENTS.md" 'the v0.4.0 refinement changes Latest acquisition to the homepage semantic tab/target'

echo "CURRENT_AUTHORITY_VERIFY_PASS"
