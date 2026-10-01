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

source_commit="$(git -C "$repo_root" rev-parse --verify --end-of-options "${source_ref}^{commit}" 2>/dev/null)" || {
  echo "KSH_RELEASE_BUILD_FAIL: source ref is not a commit: $source_ref" >&2
  exit 1
}
if [[ ! "$source_commit" =~ ^[0-9a-f]{40}$ ]]; then
  echo "KSH_RELEASE_BUILD_FAIL: unable to resolve immutable source commit: $source_ref" >&2
  exit 1
fi
if ! git -C "$repo_root" cat-file -e "${source_commit}:${plugin_rel}" 2>/dev/null; then
  echo "KSH_RELEASE_BUILD_FAIL: plugin subtree missing at source ref: $source_ref" >&2
  exit 1
fi

version="$($script_dir/ksh-kanoon-articles-version.sh --ref "$source_commit")"
source_epoch="$(git -C "$repo_root" show -s --format=%ct "$source_commit")"
if [[ ! "$source_epoch" =~ ^[0-9]+$ ]]; then
  echo "KSH_RELEASE_BUILD_FAIL: unable to resolve deterministic source timestamp: $source_commit" >&2
  exit 1
fi

mkdir -p "$output_dir"
artifact="$output_dir/ksh-kanoon-articles-v${version}.zip"
rm -f "$artifact"

# Build directly from the immutable Git object. A subtree archive is a tree
# archive, so Git otherwise uses build wall-clock time for ZIP entry mtimes.
# Pin the metadata to the immutable source commit time and UTC as part of the
# canonical byte identity.
TZ=UTC git -C "$repo_root" archive \
  --format=zip \
  --mtime="@${source_epoch}" \
  --prefix='ksh-kanoon-articles/' \
  --output="$artifact" \
  "${source_commit}:${plugin_rel}"

if [[ ! -s "$artifact" ]]; then
  echo "KSH_RELEASE_BUILD_FAIL: archive was not created" >&2
  exit 1
fi

printf '%s\n' "$artifact"
