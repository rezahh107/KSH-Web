#!/usr/bin/env bash
set -euo pipefail

script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
repo_root="$(cd "$script_dir/../.." && pwd)"
tmp_dir="$(mktemp -d)"
trap 'rm -rf "$tmp_dir"' EXIT

fail() {
  echo "KSH_RELEASE_SYSTEM_TEST_FAIL: $*" >&2
  exit 1
}

expect_failure() {
  local label="$1"
  shift
  if "$@" >/dev/null 2>&1; then
    fail "$label unexpectedly succeeded"
  fi
}

version="$($script_dir/ksh-kanoon-articles-version.sh)"
[[ -n "$version" ]] || fail 'version resolver returned empty version'

artifact="$($script_dir/build-ksh-kanoon-articles.sh HEAD "$tmp_dir/build")"
"$script_dir/verify-ksh-kanoon-articles-zip.sh" "$artifact" "$version" >/dev/null

expected_files="$(git -C "$repo_root" ls-tree -r --name-only HEAD:wp-content/plugins/ksh-kanoon-articles | sort)"
actual_files="$(unzip -Z1 "$artifact" | sed -n 's#^ksh-kanoon-articles/##p' | sed '/^$/d; /\/$/d' | sort)"
[[ "$actual_files" == "$expected_files" ]] || fail 'built artifact file set differs from exact plugin subtree'

expect_failure 'nonexistent source ref' \
  "$script_dir/build-ksh-kanoon-articles.sh" 'refs/heads/does-not-exist' "$tmp_dir/missing-ref"

wrong_name="$tmp_dir/ksh-kanoon-articles-v0.0.0.zip"
cp "$artifact" "$wrong_name"
expect_failure 'wrong artifact filename/version' \
  "$script_dir/verify-ksh-kanoon-articles-zip.sh" "$wrong_name" "$version"

printf 'not a zip\n' > "$tmp_dir/ksh-kanoon-articles-v${version}.zip"
expect_failure 'malformed archive' \
  "$script_dir/verify-ksh-kanoon-articles-zip.sh" "$tmp_dir/ksh-kanoon-articles-v${version}.zip" "$version"

# Version declarations must fail closed when a mirror drifts.
mkdir -p "$tmp_dir/plugin-copy"
unzip -q "$artifact" -d "$tmp_dir/plugin-copy"
plugin_copy="$tmp_dir/plugin-copy/ksh-kanoon-articles"
sed -i "0,/const VERSION = '[^']*';/s//const VERSION = '9.9.9';/" "$plugin_copy/includes/class-plugin.php"
expect_failure 'version mirror mismatch' \
  "$script_dir/ksh-kanoon-articles-version.sh" --plugin-dir "$plugin_copy"

# The verifier must reject development-only content even when the ZIP itself is valid.
fixture="$tmp_dir/fixture"
mkdir -p "$fixture/plugin/tests" "$fixture/plugin/includes" "$fixture/plugin/assets"
cp "$repo_root/wp-content/plugins/ksh-kanoon-articles/ksh-kanoon-articles.php" "$fixture/plugin/"
cp "$repo_root/wp-content/plugins/ksh-kanoon-articles/includes/class-plugin.php" "$fixture/plugin/includes/"
printf '<?php\n' > "$fixture/plugin/tests/forbidden.php"
(
  cd "$fixture"
  git init -q
  git config user.email 'release-test@example.invalid'
  git config user.name 'KSH Release Test'
  git add plugin
  git commit -qm fixture
  git archive --format=zip --prefix='ksh-kanoon-articles/' --output="$tmp_dir/forbidden.zip" HEAD:plugin
)
mv "$tmp_dir/forbidden.zip" "$tmp_dir/ksh-kanoon-articles-v${version}.zip"
expect_failure 'forbidden development content' \
  "$script_dir/verify-ksh-kanoon-articles-zip.sh" "$tmp_dir/ksh-kanoon-articles-v${version}.zip" "$version"

notes="$tmp_dir/notes.md"
cat > "$notes" <<'NOTES'
## What changed
- Bounded release-system test fixture.

## Real-host qualification
- NOT_PROVEN in this deterministic test.

## Evidence boundary
- This is test-only content.
NOTES
"$script_dir/verify-ksh-kanoon-articles-release-notes.sh" "$notes" >/dev/null
sed -i '/## Evidence boundary/,$d' "$notes"
expect_failure 'missing evidence-boundary notes' \
  "$script_dir/verify-ksh-kanoon-articles-release-notes.sh" "$notes"

printf 'KSH_RELEASE_SYSTEM_TEST_PASS version=%s\n' "$version"
