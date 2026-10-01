#!/usr/bin/env bash
set -euo pipefail

script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
repo_root="$(cd "$script_dir/../.." && pwd)"
tmp_dir="$(mktemp -d)"
trap 'rm -rf "$tmp_dir"' EXIT

fail() {
  echo "KSH_STARTER_RECOVERY_TEST_FAIL: $*" >&2
  exit 1
}

extract_workflow_function() {
  local begin_marker="$1"
  local end_marker="$2"
  local output="$3"
  awk -v begin_marker="$begin_marker" -v end_marker="$end_marker" '
    index($0, begin_marker) { capture=1; next }
    index($0, end_marker) { capture=0; next }
    capture {
      sub(/^          /, "")
      print
    }
  ' "$workflow" >> "$output"
}

expect_state() {
  local label="$1"
  local expected="$2"
  shift 2
  local actual
  actual="$(classify_publication_state "$@")" || fail "$label unexpectedly failed"
  [[ "$actual" == "$expected" ]] || fail "$label expected=$expected actual=$actual"
}

expect_state_failure() {
  local label="$1"
  shift
  if classify_publication_state "$@" >/dev/null 2>&1; then
    fail "$label unexpectedly produced an admissible state"
  fi
}

workflow="$repo_root/.github/workflows/publish-ksh-kanoon-articles.yml"
prepare_job="$tmp_dir/prepare-job.yml"
publish_job="$tmp_dir/publish-job.yml"
awk '/^  prepare:/{capture=1} /^  publish:/{capture=0} capture' "$workflow" > "$prepare_job"
awk '/^  publish:/{capture=1} capture' "$workflow" > "$publish_job"
[[ -s "$prepare_job" && -s "$publish_job" ]] || fail 'release workflow jobs could not be isolated'

grep -Fq 'contents: read' "$prepare_job" || fail 'prepare job lost read-only contents permission'
if grep -Fq 'contents: write' "$prepare_job"; then
  fail 'prepare job unexpectedly has write authority'
fi
grep -Fq 'contents: write' "$publish_job" || fail 'publish job lost bounded write authority'
for forbidden in \
  'actions/checkout@' \
  'setup-php@' \
  'verify-foundation.sh' \
  'composer ' \
  'build-ksh-kanoon-articles.sh' \
  'verify-ksh-kanoon-articles-zip.sh' \
  'php -l'; do
  if grep -Fq "$forbidden" "$publish_job"; then
    fail "publish job executes forbidden repository/dependency verification code: $forbidden"
  fi
done
if grep -Fq -- '--clobber' "$publish_job"; then
  fail 'publish job must never use general asset clobber semantics'
fi

state_lib="$tmp_dir/publication-state-lib.sh"
: > "$state_lib"
extract_workflow_function 'BEGIN KSH_RELEASE_ASSET_STATE_CLASSIFIER_FUNCTION' 'END KSH_RELEASE_ASSET_STATE_CLASSIFIER_FUNCTION' "$state_lib"
extract_workflow_function 'BEGIN KSH_PUBLICATION_STATE_CLASSIFIER_FUNCTION' 'END KSH_PUBLICATION_STATE_CLASSIFIER_FUNCTION' "$state_lib"
extract_workflow_function 'BEGIN KSH_APPLY_PUBLICATION_STATE_FUNCTION' 'END KSH_APPLY_PUBLICATION_STATE_FUNCTION' "$state_lib"
[[ -s "$state_lib" ]] || fail 'starter recovery test seam could not be extracted from workflow'
# shellcheck disable=SC1090
source "$state_lib"

export GITHUB_REPOSITORY='rezahh107/KSH-Web'
expected_target='aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'
wrong_target='bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb'
expected_title='KSH Kanoon Articles v9.9.9'
asset_name='ksh-kanoon-articles-v9.9.9.zip'
expected_sha='1111111111111111111111111111111111111111111111111111111111111111'
tag='ksh-kanoon-articles-v9.9.9'
probe_dir="$tmp_dir/probe"
mkdir -p "$probe_dir"

asset_probe() {
  local release_json="$1"
  classify_release_asset_state "$release_json" "$asset_name" "$expected_sha" "$tag" "$probe_dir"
}

assert_asset_probe() {
  local label="$1"
  local release_json="$2"
  local expected_state="$3"
  local expected_id="$4"
  local actual
  actual="$(asset_probe "$release_json")" || fail "$label asset probe unexpectedly failed"
  [[ "$actual" == "${expected_state}|${expected_id}" ]] || fail "$label expected=${expected_state}|${expected_id} actual=$actual"
}

starter_json="$tmp_dir/starter.json"
jq -n --arg name "$asset_name" '{assets:[{id:4242,name:$name,state:"starter",size:0,digest:null}]}' > "$starter_json"
assert_asset_probe 'documented empty starter asset' "$starter_json" 'STARTER_RECOVERABLE' '4242'
expect_state 'matching Release with starter asset' 'RELEASE_MATCHING_STARTER_ASSET' \
  'FOUND' 'FOUND' "$expected_target" "$expected_target" "$expected_title" "$expected_title" 'false' 'false' 'true' 'STARTER_RECOVERABLE'

starter_nonempty_json="$tmp_dir/starter-nonempty.json"
jq -n --arg name "$asset_name" '{assets:[{id:4242,name:$name,state:"starter",size:1,digest:null}]}' > "$starter_nonempty_json"
assert_asset_probe 'non-empty starter asset is not recoverable' "$starter_nonempty_json" 'STARTER_INVALID' ''
expect_state_failure 'non-empty starter state fails closed' \
  'FOUND' 'FOUND' "$expected_target" "$expected_target" "$expected_title" "$expected_title" 'false' 'false' 'true' 'STARTER_INVALID'

uploaded_mismatch_json="$tmp_dir/uploaded-mismatch.json"
jq -n --arg name "$asset_name" '{assets:[{id:4343,name:$name,state:"uploaded",size:123,digest:"sha256:2222222222222222222222222222222222222222222222222222222222222222"}]}' > "$uploaded_mismatch_json"
assert_asset_probe 'ordinary uploaded mismatching asset' "$uploaded_mismatch_json" 'MISMATCH' ''
expect_state_failure 'ordinary uploaded mismatching asset remains conflict' \
  'FOUND' 'FOUND' "$expected_target" "$expected_target" "$expected_title" "$expected_title" 'false' 'false' 'true' 'MISMATCH'

starter_plus_unexpected_json="$tmp_dir/starter-plus-unexpected.json"
jq -n --arg name "$asset_name" '{assets:[{id:4242,name:$name,state:"starter",size:0,digest:null},{id:4545,name:"unexpected.txt",state:"uploaded",size:1,digest:null}]}' > "$starter_plus_unexpected_json"
assert_asset_probe 'starter plus unexpected asset' "$starter_plus_unexpected_json" 'AMBIGUOUS' ''
expect_state_failure 'starter plus unexpected asset fails closed' \
  'FOUND' 'FOUND' "$expected_target" "$expected_target" "$expected_title" "$expected_title" 'false' 'false' 'true' 'AMBIGUOUS'

unknown_json="$tmp_dir/unknown-state.json"
jq -n --arg name "$asset_name" '{assets:[{id:4646,name:$name,state:"processing",size:0,digest:null}]}' > "$unknown_json"
assert_asset_probe 'unknown GitHub asset state' "$unknown_json" 'UNKNOWN' ''
expect_state_failure 'unknown asset state fails closed' \
  'FOUND' 'FOUND' "$expected_target" "$expected_target" "$expected_title" "$expected_title" 'false' 'false' 'true' 'UNKNOWN'

expect_state_failure 'wrong tag target with starter fails closed' \
  'FOUND' 'FOUND' "$wrong_target" "$expected_target" "$expected_title" "$expected_title" 'false' 'false' 'true' 'STARTER_RECOVERABLE'
expect_state_failure 'Release metadata mismatch with starter fails closed' \
  'FOUND' 'FOUND' "$expected_target" "$expected_target" 'Wrong title' "$expected_title" 'false' 'false' 'true' 'STARTER_RECOVERABLE'
expect_state_failure 'Release notes mismatch with starter fails closed' \
  'FOUND' 'FOUND' "$expected_target" "$expected_target" "$expected_title" "$expected_title" 'false' 'false' 'false' 'STARTER_RECOVERABLE'

mock_bin="$tmp_dir/mock-bin"
mkdir -p "$mock_bin"
cat > "$mock_bin/gh" <<'MOCK_GH'
#!/usr/bin/env bash
set -euo pipefail
printf '%s\n' "$*" >> "${MOCK_GH_LOG:?}"
if [[ "${1:-}" == 'api' && "$*" == *'--method DELETE'* ]]; then
  exit "${MOCK_GH_DELETE_EXIT:-0}"
fi
if [[ "${1:-}" == 'release' && "${2:-}" == 'upload' ]]; then
  exit "${MOCK_GH_UPLOAD_EXIT:-0}"
fi
exit 0
MOCK_GH
chmod +x "$mock_bin/gh"

notes_fixture="$tmp_dir/notes.md"
artifact_fixture="$tmp_dir/artifact.zip"
printf 'notes\n' > "$notes_fixture"
printf 'qualified artifact\n' > "$artifact_fixture"

run_apply() {
  local state="$1"
  local starter_asset_id="$2"
  local log="$3"
  local delete_exit="${4:-0}"
  local upload_exit="${5:-0}"
  : > "$log"
  PATH="$mock_bin:$PATH" \
    MOCK_GH_LOG="$log" \
    MOCK_GH_DELETE_EXIT="$delete_exit" \
    MOCK_GH_UPLOAD_EXIT="$upload_exit" \
    apply_publication_state \
      "$state" "$expected_target" "$tag" "$expected_title" "$notes_fixture" "$artifact_fixture" "$starter_asset_id"
}

starter_recovery_log="$tmp_dir/starter-recovery.log"
run_apply 'RELEASE_MATCHING_STARTER_ASSET' '4242' "$starter_recovery_log"
[[ "$(wc -l < "$starter_recovery_log")" -eq 2 ]] || fail 'starter recovery must perform exactly delete then upload'
grep -Fxq 'api --method DELETE repos/rezahh107/KSH-Web/releases/assets/4242' <(sed -n '1p' "$starter_recovery_log") || fail 'starter recovery did not delete exact classified asset ID first'
grep -Fq 'release upload ksh-kanoon-articles-v9.9.9' <(sed -n '2p' "$starter_recovery_log") || fail 'starter recovery did not upload exact qualified artifact after deletion'
if grep -Fq 'git/refs' "$starter_recovery_log"; then
  fail 'starter recovery attempted to move/recreate tag'
fi
if grep -Fq -- '--clobber' "$starter_recovery_log"; then
  fail 'starter recovery used forbidden asset overwrite semantics'
fi

delete_failure_log="$tmp_dir/delete-failure.log"
set +e
run_apply 'RELEASE_MATCHING_STARTER_ASSET' '4242' "$delete_failure_log" 1 0 >/dev/null 2>&1
delete_failure_status=$?
set -e
(( delete_failure_status != 0 )) || fail 'starter deletion failure unexpectedly succeeded'
grep -Fq 'api --method DELETE repos/rezahh107/KSH-Web/releases/assets/4242' "$delete_failure_log" || fail 'delete-failure case did not attempt exact starter deletion'
if grep -Fq 'release upload' "$delete_failure_log"; then
  fail 'asset upload was attempted after uncertain/failed starter deletion'
fi

upload_failure_log="$tmp_dir/upload-failure.log"
set +e
run_apply 'RELEASE_MATCHING_STARTER_ASSET' '4242' "$upload_failure_log" 0 1 >/dev/null 2>&1
upload_failure_status=$?
set -e
(( upload_failure_status != 0 )) || fail 'post-delete upload failure unexpectedly succeeded'
grep -Fq 'api --method DELETE repos/rezahh107/KSH-Web/releases/assets/4242' "$upload_failure_log" || fail 'post-delete upload-failure case did not delete starter first'
grep -Fq 'release upload ksh-kanoon-articles-v9.9.9' "$upload_failure_log" || fail 'post-delete upload-failure case did not attempt canonical upload'
if grep -Fq -- '--clobber' "$upload_failure_log"; then
  fail 'post-delete retry path used forbidden clobber semantics'
fi

published_log="$tmp_dir/published.log"
run_apply 'PUBLISHED_MATCHING' '' "$published_log"
[[ ! -s "$published_log" ]] || fail 'PUBLISHED_MATCHING must remain mutation-free'

mismatch_log="$tmp_dir/mismatch.log"
set +e
run_apply 'MISMATCH' '4242' "$mismatch_log" 0 0 >/dev/null 2>&1
mismatch_status=$?
set -e
(( mismatch_status != 0 )) || fail 'ordinary mismatching asset state unexpectedly entered mutation path'
[[ ! -s "$mismatch_log" ]] || fail 'ordinary mismatching asset invoked deletion/upload mutation'

printf 'KSH_STARTER_RECOVERY_TEST_PASS\n'
