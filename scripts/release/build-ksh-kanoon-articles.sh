#!/usr/bin/env bash
set -euo pipefail

script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
repo_root="$(cd "$script_dir/../.." && pwd)"
source_ref="${1:-}"
output_dir="${2:-$repo_root/dist}"
plugin_rel='wp-content/plugins/ksh-kanoon-articles'

if [[ -z "$source_ref" ]]; then
  echo "Usage: $0 <source-commit-or-ref> [output-directory]" >&2
  exit 2
fi

if ! git -C "$repo_root" cat-file -e "${source_ref}^{commit}" 2>/dev/null; then
  echo "KSH_RELEASE_BUILD_FAIL: source ref is not a commit: $source_ref" >&2
  exit 1
fi
if ! git -C "$repo_root" cat-file -e "${source_ref}:${plugin_rel}" 2>/dev/null; then
  echo "KSH_RELEASE_BUILD_FAIL: plugin subtree missing at source ref: $source_ref" >&2
  exit 1
fi

version="$($script_dir/ksh-kanoon-articles-version.sh --ref "$source_ref")"
mkdir -p "$output_dir"
artifact="$output_dir/ksh-kanoon-articles-v${version}.zip"
rm -f "$artifact"

# Build directly from the immutable Git object, not the runner working tree.
git -C "$repo_root" archive \
  --format=zip \
  --prefix='ksh-kanoon-articles/' \
  --output="$artifact" \
  "${source_ref}:${plugin_rel}"

if [[ ! -s "$artifact" ]]; then
  echo "KSH_RELEASE_BUILD_FAIL: archive was not created" >&2
  exit 1
fi

printf '%s\n' "$artifact"
