#!/usr/bin/env bash
set -euo pipefail

notes_path="${1:-}"
if [[ -z "$notes_path" ]]; then
  echo "Usage: $0 <release-notes.md>" >&2
  exit 2
fi
if [[ ! -s "$notes_path" ]]; then
  echo "KSH_RELEASE_NOTES_FAIL: release notes are missing or empty: $notes_path" >&2
  exit 1
fi

for heading in '## What changed' '## Real-host qualification' '## Evidence boundary'; do
  if ! grep -Fxq "$heading" "$notes_path"; then
    echo "KSH_RELEASE_NOTES_FAIL: required heading missing: $heading" >&2
    exit 1
  fi
done

if grep -Eq '(^|[^A-Za-z])(TODO|TBD)([^A-Za-z]|$)' "$notes_path"; then
  echo "KSH_RELEASE_NOTES_FAIL: unresolved TODO/TBD marker remains" >&2
  exit 1
fi

printf 'KSH_RELEASE_NOTES_PASS\n'
