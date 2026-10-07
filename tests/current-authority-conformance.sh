#!/usr/bin/env bash
set -euo pipefail

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
verifier="$repo_root/scripts/verify-current-authority.sh"
tmp_root="$(mktemp -d)"
trap 'rm -rf "$tmp_root"' EXIT

files=(
  "docs/MOTHER_PROJECT.md"
  "README.md"
  "AGENTS.md"
  "docs/design/PAGE05_PRODUCTION_REFERENCE.md"
  "docs/design/UI_REFERENCE.md"
  "docs/decisions/ADR-001-kanoon-article-list-mirror.md"
)

make_fixture() {
  local fixture="$tmp_root/fixture"
  rm -rf "$fixture"
  mkdir -p "$fixture"
  local path
  for path in "${files[@]}"; do
    mkdir -p "$fixture/$(dirname "$path")"
    cp "$repo_root/$path" "$fixture/$path"
  done
  printf '%s\n' "$fixture"
}

expect_pass() {
  local label="$1"
  local fixture="$2"
  if ! bash "$verifier" "$fixture" >/dev/null; then
    echo "CURRENT_AUTHORITY_TEST_FAIL: expected PASS: $label" >&2
    exit 1
  fi
  echo "CURRENT_AUTHORITY_CONTROL_PASS: $label"
}

expect_fail() {
  local label="$1"
  local fixture="$2"
  if bash "$verifier" "$fixture" >/dev/null 2>&1; then
    echo "CURRENT_AUTHORITY_TEST_FAIL: mutation unexpectedly passed: $label" >&2
    exit 1
  fi
  echo "CURRENT_AUTHORITY_MUTATION_PASS: $label"
}

fixture="$(make_fixture)"
expect_pass "baseline" "$fixture"

fixture="$(make_fixture)"
printf '\nELEMENTOR_RUNTIME_STATUS = PROVEN\n' >> "$fixture/docs/MOTHER_PROJECT.md"
expect_fail "runtime-state-contradiction" "$fixture"

fixture="$(make_fixture)"
sed -i 's/KSH_PAGE05_MASTER_REFERENCE_v1\.1/KSH_PAGE05_MASTER_REFERENCE_v9.9/' "$fixture/README.md"
expect_fail "master-version-summary-drift" "$fixture"

fixture="$(make_fixture)"
sed -i 's/2b2a783fd98b68ec97867f7a686cfcc79993c7ee3e3bf60aff77a965f8b81597/aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa/' "$fixture/docs/design/PAGE05_PRODUCTION_REFERENCE.md"
expect_fail "desktop-master-hash-drift" "$fixture"

fixture="$(make_fixture)"
sed -i 's/1440 × 3515 px/1441 × 3515 px/' "$fixture/docs/design/PAGE05_PRODUCTION_REFERENCE.md"
expect_fail "desktop-master-dimension-drift" "$fixture"

fixture="$(make_fixture)"
sed -i 's/KSH_PAGE05_HERO_REFERENCE_v1\.0/KSH_PAGE05_HERO_REFERENCE_v9.9/' "$fixture/AGENTS.md"
expect_fail "hero-identity-summary-drift" "$fixture"

fixture="$(make_fixture)"
sed -i 's/DESIGN_SYSTEM_STATUS = COMPLETE/DESIGN_SYSTEM_STATUS = DRAFT/' "$fixture/docs/design/UI_REFERENCE.md"
expect_fail "repeated-authority-state-drift" "$fixture"

fixture="$(make_fixture)"
sed -i 's/current v0\.4\.1 real-host acquisition-contract qualification/current v0.4.0 real-host acquisition-contract qualification/' "$fixture/AGENTS.md"
expect_fail "stale-current-article-qualification" "$fixture"

fixture="$(make_fixture)"
expect_pass "restored-positive-control" "$fixture"

echo "CURRENT_AUTHORITY_REGRESSION_PASS mutations=7 positive_controls=2"
