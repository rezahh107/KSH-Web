#!/usr/bin/env bash
set -euo pipefail

script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
repo_root="$(cd "$script_dir/../.." && pwd)"
tmp_dir="$(mktemp -d)"
changed_worktree=''

cleanup() {
  if [[ -n "$changed_worktree" && -d "$changed_worktree" ]]; then
    git -C "$repo_root" worktree remove --force "$changed_worktree" >/dev/null 2>&1 || true
  fi
  rm -rf "$tmp_dir"
}
trap cleanup EXIT

fail() {
  echo "KSH_RELEASE_DETERMINISM_TEST_FAIL: $*" >&2
  exit 1
}

archive_timestamps() {
  TZ=UTC unzip -Z -T "$1" | awk '$1 ~ /^[-dl]/ { print $(NF - 1) }' | sort -u
}

for command_name in git unzip sha256sum cmp; do
  command -v "$command_name" >/dev/null 2>&1 || fail "required command missing: $command_name"
done

plugin_rel='wp-content/plugins/ksh-kanoon-articles'
source_commit="$(git -C "$repo_root" rev-parse --verify HEAD^{commit})"
version="$($script_dir/ksh-kanoon-articles-version.sh --ref "$source_commit")"
expected_timestamp="$(TZ=UTC git -C "$repo_root" show -s --format=%cd --date=format-local:%Y%m%d.%H%M%S "$source_commit")"
[[ "$expected_timestamp" =~ ^[0-9]{8}\.[0-9]{6}$ ]] || fail 'unable to derive expected source timestamp'

artifact_one="$(TZ=Pacific/Honolulu "$script_dir/build-ksh-kanoon-articles.sh" "$source_commit" "$tmp_dir/build-one")"
"$script_dir/verify-ksh-kanoon-articles-zip.sh" "$artifact_one" "$version" >/dev/null
sha_one="$(sha256sum "$artifact_one" | awk '{print $1}')"

# Wait beyond the historical ZIP timestamp window and vary the caller timezone
# so the old wall-clock/tree-archive behavior cannot pass accidentally.
sleep 3
artifact_two="$(TZ=Asia/Tokyo "$script_dir/build-ksh-kanoon-articles.sh" "$source_commit" "$tmp_dir/build-two")"
"$script_dir/verify-ksh-kanoon-articles-zip.sh" "$artifact_two" "$version" >/dev/null
sha_two="$(sha256sum "$artifact_two" | awk '{print $1}')"

[[ "$sha_one" == "$sha_two" ]] || fail "same immutable source produced different SHA-256 values: $sha_one != $sha_two"
cmp -s "$artifact_one" "$artifact_two" || fail 'same immutable source did not produce byte-identical ZIPs'

for artifact in "$artifact_one" "$artifact_two"; do
  timestamps="$(archive_timestamps "$artifact")"
  [[ "$timestamps" == "$expected_timestamp" ]] || fail "archive timestamps are not pinned to source commit time: expected=$expected_timestamp actual=$timestamps"
done

expected_files="$(git -C "$repo_root" ls-tree -r --name-only "$source_commit:$plugin_rel" | sort)"
actual_files="$(unzip -Z1 "$artifact_one" | sed -n 's#^ksh-kanoon-articles/##p' | sed '/^$/d; /\/$/d' | sort)"
[[ "$actual_files" == "$expected_files" ]] || fail 'deterministic artifact file set differs from exact plugin subtree'

# Prove source identity still matters. Change one packaged byte in a detached
# temporary worktree, but commit it with the same timestamp as the source so
# the identity change cannot be explained by metadata time alone.
changed_worktree="$tmp_dir/changed-source"
git -C "$repo_root" worktree add --detach "$changed_worktree" "$source_commit" >/dev/null
changed_file="$changed_worktree/$plugin_rel/assets/css/frontend.css"
[[ -f "$changed_file" ]] || fail 'source-change fixture file is missing'
printf '\n/* deterministic-release-source-change */\n' >> "$changed_file"
git -C "$changed_worktree" config user.email 'release-test@example.invalid'
git -C "$changed_worktree" config user.name 'KSH Release Test'
git -C "$changed_worktree" add "$plugin_rel/assets/css/frontend.css"
source_commit_date="$(git -C "$repo_root" show -s --format=%cI "$source_commit")"
GIT_AUTHOR_DATE="$source_commit_date" GIT_COMMITTER_DATE="$source_commit_date" \
  git -C "$changed_worktree" commit -qm 'deterministic release source identity fixture'
changed_commit="$(git -C "$changed_worktree" rev-parse HEAD)"
changed_artifact="$(TZ=UTC "$changed_worktree/scripts/release/build-ksh-kanoon-articles.sh" "$changed_commit" "$tmp_dir/build-changed")"
"$changed_worktree/scripts/release/verify-ksh-kanoon-articles-zip.sh" "$changed_artifact" "$version" >/dev/null
changed_sha="$(sha256sum "$changed_artifact" | awk '{print $1}')"
[[ "$changed_sha" != "$sha_one" ]] || fail 'changed plugin source did not change artifact identity'
changed_timestamps="$(archive_timestamps "$changed_artifact")"
[[ "$changed_timestamps" == "$expected_timestamp" ]] || fail 'source-change fixture did not preserve the controlled commit timestamp'

printf 'KSH_RELEASE_DETERMINISM_TEST_PASS version=%s\n' "$version"
printf 'source_commit=%s\n' "$source_commit"
printf 'archive_timestamp_utc=%s\n' "$expected_timestamp"
printf 'first_sha256=%s\n' "$sha_one"
printf 'second_sha256=%s\n' "$sha_two"
printf 'changed_source_sha256=%s\n' "$changed_sha"
