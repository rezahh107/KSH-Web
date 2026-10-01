#!/usr/bin/env bash
set -euo pipefail

script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
zip_path="${1:-}"
expected_version="${2:-}"

if [[ -z "$zip_path" || -z "$expected_version" ]]; then
  echo "Usage: $0 <zip-path> <expected-version>" >&2
  exit 2
fi
if [[ ! -f "$zip_path" ]]; then
  echo "KSH_RELEASE_ZIP_VERIFY_FAIL: ZIP does not exist: $zip_path" >&2
  exit 1
fi

for command_name in unzip sha256sum php; do
  if ! command -v "$command_name" >/dev/null 2>&1; then
    echo "KSH_RELEASE_ZIP_VERIFY_FAIL: required command missing: $command_name" >&2
    exit 1
  fi
done

expected_name="ksh-kanoon-articles-v${expected_version}.zip"
if [[ "$(basename "$zip_path")" != "$expected_name" ]]; then
  echo "KSH_RELEASE_ZIP_VERIFY_FAIL: expected filename $expected_name" >&2
  exit 1
fi

if ! unzip -tq "$zip_path" >/dev/null; then
  echo "KSH_RELEASE_ZIP_VERIFY_FAIL: archive integrity check failed" >&2
  exit 1
fi

entries="$(unzip -Z1 "$zip_path")"
if [[ -z "$entries" ]]; then
  echo "KSH_RELEASE_ZIP_VERIFY_FAIL: archive is empty" >&2
  exit 1
fi

while IFS= read -r entry; do
  [[ -n "$entry" ]] || continue
  if [[ "$entry" != ksh-kanoon-articles/* ]]; then
    echo "KSH_RELEASE_ZIP_VERIFY_FAIL: unexpected top-level entry: $entry" >&2
    exit 1
  fi
  if [[ "$entry" == /* || "$entry" == *'../'* || "$entry" == *'/..' || "$entry" == *'\\'* ]]; then
    echo "KSH_RELEASE_ZIP_VERIFY_FAIL: unsafe archive path: $entry" >&2
    exit 1
  fi
  if [[ "$entry" =~ ^ksh-kanoon-articles/(tests|scripts|docs|vendor|\.git|\.github|elementor)(/|$) ]]; then
    echo "KSH_RELEASE_ZIP_VERIFY_FAIL: forbidden release content: $entry" >&2
    exit 1
  fi
  if [[ "$entry" =~ ^ksh-kanoon-articles/(composer\.json|composer\.lock)$ ]]; then
    echo "KSH_RELEASE_ZIP_VERIFY_FAIL: Composer development metadata is not a runtime artifact: $entry" >&2
    exit 1
  fi
done <<<"$entries"

for required in \
  'ksh-kanoon-articles/ksh-kanoon-articles.php' \
  'ksh-kanoon-articles/includes/class-plugin.php' \
  'ksh-kanoon-articles/assets/' \
  'ksh-kanoon-articles/includes/'; do
  if ! grep -Fxq "$required" <<<"$entries"; then
    echo "KSH_RELEASE_ZIP_VERIFY_FAIL: required archive entry missing: $required" >&2
    exit 1
  fi
done

tmp_dir="$(mktemp -d)"
trap 'rm -rf "$tmp_dir"' EXIT
unzip -q "$zip_path" -d "$tmp_dir"
plugin_dir="$tmp_dir/ksh-kanoon-articles"

actual_version="$($script_dir/ksh-kanoon-articles-version.sh --plugin-dir "$plugin_dir")"
if [[ "$actual_version" != "$expected_version" ]]; then
  echo "KSH_RELEASE_ZIP_VERIFY_FAIL: packaged version=$actual_version expected=$expected_version" >&2
  exit 1
fi

php_files=0
while IFS= read -r -d '' php_file; do
  php -l "$php_file" >/dev/null
  php_files=$((php_files + 1))
done < <(find "$plugin_dir" -type f -name '*.php' -print0 | sort -z)
if (( php_files == 0 )); then
  echo "KSH_RELEASE_ZIP_VERIFY_FAIL: no PHP files found in package" >&2
  exit 1
fi

sha256="$(sha256sum "$zip_path" | awk '{print $1}')"
printf 'KSH_RELEASE_ZIP_VERIFY_PASS\n'
printf 'version=%s\n' "$actual_version"
printf 'php_files=%s\n' "$php_files"
printf 'sha256=%s\n' "$sha256"
