#!/usr/bin/env bash
set -euo pipefail

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$repo_root"

required_files=(
  "README.md"
  "AGENTS.md"
  ".gitignore"
  ".gitattributes"
  ".editorconfig"
  "docs/MOTHER_PROJECT.md"
  "docs/REPOSITORY_FOUNDATION.md"
  "docs/design/UI_REFERENCE.md"
  "docs/design/assets/homepage-responsive-reference.webp"
  "docs/decisions/ADR-001-kanoon-article-list-mirror.md"
)

for path in "${required_files[@]}"; do
  if [[ ! -s "$path" ]]; then
    echo "FOUNDATION_VERIFY_FAIL: required non-empty file missing: $path" >&2
    exit 1
  fi
done

grep -Fq 'docs/MOTHER_PROJECT.md' README.md
grep -Fq 'docs/design/UI_REFERENCE.md' README.md
grep -Fq 'scripts/verify-foundation.sh' README.md
grep -Fq 'docs/MOTHER_PROJECT.md' AGENTS.md
grep -Fq 'ADR-001-kanoon-article-list-mirror.md' AGENTS.md
grep -Fq 'bash scripts/verify-foundation.sh' AGENTS.md

expected_design_blob='4f821a2e0c3a03c897c28eefb50d8ac7312359ce'
actual_design_blob="$(git hash-object docs/design/assets/homepage-responsive-reference.webp)"
if [[ "$actual_design_blob" != "$expected_design_blob" ]]; then
  echo "FOUNDATION_VERIFY_FAIL: design reference blob mismatch: expected $expected_design_blob, got $actual_design_blob" >&2
  exit 1
fi

tracked="$(git ls-files)"
for forbidden in '.env' 'wp-config.php'; do
  if grep -Fxq "$forbidden" <<<"$tracked"; then
    echo "FOUNDATION_VERIFY_FAIL: forbidden sensitive/runtime file is tracked: $forbidden" >&2
    exit 1
  fi
done

if grep -Eq '(^|/)(id_rsa|id_ed25519|[^/]+\.pem|[^/]+\.key)$' <<<"$tracked"; then
  echo "FOUNDATION_VERIFY_FAIL: possible private-key material is tracked" >&2
  exit 1
fi

echo "FOUNDATION_VERIFY_PASS"
