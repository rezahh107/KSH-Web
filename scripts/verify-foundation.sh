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
  "composer.json"
  "phpcs.xml.dist"
  "docs/MOTHER_PROJECT.md"
  "docs/REPOSITORY_FOUNDATION.md"
  "docs/design/UI_REFERENCE.md"
  "docs/design/PAGE05_PRODUCTION_REFERENCE.md"
  "docs/design/assets/homepage-responsive-reference.webp"
  "docs/decisions/ADR-001-kanoon-article-list-mirror.md"
  "docs/decisions/ADR-002-ksh-kanoon-articles-release-system.md"
  "docs/releases/ksh-kanoon-articles/TEMPLATE.md"
  "docs/releases/ksh-kanoon-articles/README.md"
  "elementor/homepage/README.md"
  "elementor/homepage/ksh-public-homepage-body-v1.json"
  "scripts/validate-elementor-homepage.php"
  "scripts/release/ksh-kanoon-articles-version.sh"
  "scripts/release/build-ksh-kanoon-articles.sh"
  "scripts/release/verify-ksh-kanoon-articles-zip.sh"
  "scripts/release/verify-ksh-kanoon-articles-release-notes.sh"
  "scripts/release/test-ksh-kanoon-articles-determinism.sh"
  "scripts/release/test-ksh-kanoon-articles-release.sh"
  "scripts/release/test-ksh-kanoon-articles-starter-recovery.sh"
  ".github/workflows/publish-ksh-kanoon-articles.yml"
  "wp-content/plugins/ksh-kanoon-articles/ksh-kanoon-articles.php"
  "tests/run.php"
)

for path in "${required_files[@]}"; do
  if [[ ! -s "$path" ]]; then
    echo "FOUNDATION_VERIFY_FAIL: required non-empty file missing: $path" >&2
    exit 1
  fi
done

grep -Fq 'docs/MOTHER_PROJECT.md' README.md
grep -Fq 'docs/design/UI_REFERENCE.md' README.md
grep -Fq 'docs/design/PAGE05_PRODUCTION_REFERENCE.md' README.md
grep -Fq 'docs/design/PAGE05_PRODUCTION_REFERENCE.md' AGENTS.md
grep -Fq 'scripts/verify-foundation.sh' README.md
grep -Fq 'wp-content/plugins/ksh-kanoon-articles' README.md
grep -Fq 'docs/MOTHER_PROJECT.md' AGENTS.md
grep -Fq 'ADR-001-kanoon-article-list-mirror.md' AGENTS.md
grep -Fq 'bash scripts/verify-foundation.sh' AGENTS.md
grep -Fq 'wp-content/plugins/ksh-kanoon-articles' AGENTS.md

# Current Page 05 authority must remain explicit and must not be upgraded from static
# design evidence to runtime/browser proof.
grep -Fq 'DESIGN_SYSTEM_STATUS = COMPLETE' docs/design/PAGE05_PRODUCTION_REFERENCE.md
grep -Fq 'PAGE_05_ELEMENTOR_PRODUCTION_REFERENCE' docs/design/PAGE05_PRODUCTION_REFERENCE.md
grep -Fq 'ELEMENTOR_RUNTIME_STATUS = NOT_PROVEN' docs/design/PAGE05_PRODUCTION_REFERENCE.md
grep -Fq 'KSH_PAGE05_MASTER_REFERENCE_v1.1' docs/design/PAGE05_PRODUCTION_REFERENCE.md
grep -Fq '2b2a783fd98b68ec97867f7a686cfcc79993c7ee3e3bf60aff77a965f8b81597' docs/design/PAGE05_PRODUCTION_REFERENCE.md
grep -Fq '8877db443487d5d9e0f5a293c713e76a3397f5bda5c03f974174f612a6f8ab37' docs/design/PAGE05_PRODUCTION_REFERENCE.md
grep -Fq 'KSH_PAGE05_HERO_REFERENCE_v1.0' docs/design/PAGE05_PRODUCTION_REFERENCE.md
grep -Fq '94e55bfc426bff42f525f0c1ec685ce9e61fffdcfee459cbb35eccd51f6e6973' docs/design/PAGE05_PRODUCTION_REFERENCE.md
grep -Fq '8bfa78723bd42770560be17707bfa5fe1323227bb8115500f42dad334100682f' docs/design/PAGE05_PRODUCTION_REFERENCE.md
grep -Fq 'PAGE_05_ELEMENTOR_PRODUCTION_REFERENCE' docs/MOTHER_PROJECT.md
grep -Fq 'ELEMENTOR_RUNTIME_STATUS = NOT_PROVEN' docs/MOTHER_PROJECT.md

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

if ! command -v php >/dev/null 2>&1; then
  echo "FOUNDATION_VERIFY_FAIL: php is required" >&2
  exit 1
fi

if ! command -v composer >/dev/null 2>&1; then
  echo "FOUNDATION_VERIFY_FAIL: composer is required" >&2
  exit 1
fi

for command_name in git unzip sha256sum; do
  if ! command -v "$command_name" >/dev/null 2>&1; then
    echo "FOUNDATION_VERIFY_FAIL: release verification requires $command_name" >&2
    exit 1
  fi
done

while IFS= read -r php_file; do
  php -l "$php_file" >/dev/null
done < <(find wp-content/plugins/ksh-kanoon-articles tests scripts -type f -name '*.php' -print | sort)

echo "PHP_SYNTAX_PASS"

php scripts/validate-elementor-homepage.php

echo "ELEMENTOR_HOMEPAGE_VERIFY_PASS"

bash scripts/release/test-ksh-kanoon-articles-determinism.sh
bash scripts/release/test-ksh-kanoon-articles-release.sh
bash scripts/release/test-ksh-kanoon-articles-starter-recovery.sh

composer install --no-interaction --no-progress --prefer-dist
composer cs
composer test

echo "FOUNDATION_VERIFY_PASS"
