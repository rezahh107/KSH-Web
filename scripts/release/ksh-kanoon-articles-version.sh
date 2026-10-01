#!/usr/bin/env bash
set -euo pipefail

script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
repo_root="$(cd "$script_dir/../.." && pwd)"
plugin_rel='wp-content/plugins/ksh-kanoon-articles'
main_rel="$plugin_rel/ksh-kanoon-articles.php"
class_rel="$plugin_rel/includes/class-plugin.php"

usage() {
  echo "Usage: $0 [--ref <git-ref> | --plugin-dir <directory>]" >&2
}

mode='worktree'
value=''
if (( $# > 0 )); then
  if (( $# != 2 )); then
    usage
    exit 2
  fi
  case "$1" in
    --ref|--plugin-dir)
      mode="${1#--}"
      value="$2"
      ;;
    *)
      usage
      exit 2
      ;;
  esac
fi

read_file() {
  local rel="$1"
  case "$mode" in
    worktree)
      cat "$repo_root/$rel"
      ;;
    ref)
      git -C "$repo_root" show "$value:$rel"
      ;;
    plugin-dir)
      case "$rel" in
        "$main_rel") cat "$value/ksh-kanoon-articles.php" ;;
        "$class_rel") cat "$value/includes/class-plugin.php" ;;
        *) return 2 ;;
      esac
      ;;
  esac
}

main_content="$(read_file "$main_rel")" || {
  echo "KSH_RELEASE_VERSION_FAIL: unable to read plugin header source" >&2
  exit 1
}
class_content="$(read_file "$class_rel")" || {
  echo "KSH_RELEASE_VERSION_FAIL: unable to read Plugin::VERSION source" >&2
  exit 1
}

header_version="$(printf '%s\n' "$main_content" | sed -nE 's/^[[:space:]]*\*[[:space:]]*Version:[[:space:]]*([^[:space:]]+).*$/\1/p')"
class_version="$(printf '%s\n' "$class_content" | sed -nE "s/^[[:space:]]*const[[:space:]]+VERSION[[:space:]]*=[[:space:]]*'([^']+)'[[:space:]]*;.*$/\1/p")"

if [[ -z "$header_version" || "$header_version" == *$'\n'* ]]; then
  echo "KSH_RELEASE_VERSION_FAIL: expected exactly one plugin header Version" >&2
  exit 1
fi
if [[ -z "$class_version" || "$class_version" == *$'\n'* ]]; then
  echo "KSH_RELEASE_VERSION_FAIL: expected exactly one Plugin::VERSION declaration" >&2
  exit 1
fi
if [[ "$header_version" != "$class_version" ]]; then
  echo "KSH_RELEASE_VERSION_FAIL: plugin header=$header_version Plugin::VERSION=$class_version" >&2
  exit 1
fi
if [[ ! "$header_version" =~ ^[0-9]+\.[0-9]+\.[0-9]+([.+-][0-9A-Za-z.-]+)*$ ]]; then
  echo "KSH_RELEASE_VERSION_FAIL: unsupported version format: $header_version" >&2
  exit 1
fi

printf '%s\n' "$header_version"
