#!/usr/bin/env bash
set -euo pipefail

script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
repo_root="$(cd "$script_dir/../.." && pwd)"
template_path="$repo_root/docs/releases/ksh-kanoon-articles/TEMPLATE.md"
notes_path="${1:-}"

if [[ -z "$notes_path" ]]; then
  echo "Usage: $0 <release-notes.md>" >&2
  exit 2
fi
if [[ ! -s "$notes_path" ]]; then
  echo "KSH_RELEASE_NOTES_FAIL: release notes are missing or empty: $notes_path" >&2
  exit 1
fi

required_headings=(
  '## What changed'
  '## Real-host qualification'
  '## Evidence boundary'
)

for heading in "${required_headings[@]}"; do
  if ! grep -Fxq "$heading" "$notes_path"; then
    echo "KSH_RELEASE_NOTES_FAIL: required heading missing: $heading" >&2
    exit 1
  fi
done

if [[ -f "$template_path" ]] && cmp -s "$notes_path" "$template_path"; then
  echo "KSH_RELEASE_NOTES_FAIL: versioned release notes must not be an unchanged copy of TEMPLATE.md" >&2
  exit 1
fi

if grep -Eq '(^|[^A-Za-z])(TODO|TBD)([^A-Za-z]|$)' "$notes_path"; then
  echo "KSH_RELEASE_NOTES_FAIL: unresolved TODO/TBD marker remains" >&2
  exit 1
fi

if ! awk '
  BEGIN {
    current = ""
    content["what"] = 0
    content["host"] = 0
    content["boundary"] = 0
  }
  $0 == "## What changed" {
    current = "what"
    next
  }
  $0 == "## Real-host qualification" {
    current = "host"
    next
  }
  $0 == "## Evidence boundary" {
    current = "boundary"
    next
  }
  current != "" {
    line = $0
    gsub(/^[[:space:]]+|[[:space:]]+$/, "", line)
    if (line != "") {
      content[current] = 1
    }
  }
  END {
    if (!content["what"] || !content["host"] || !content["boundary"]) {
      exit 1
    }
  }
' "$notes_path"; then
  echo "KSH_RELEASE_NOTES_FAIL: each mandatory section must contain at least one nonblank content line" >&2
  exit 1
fi

printf 'KSH_RELEASE_NOTES_PASS\n'
