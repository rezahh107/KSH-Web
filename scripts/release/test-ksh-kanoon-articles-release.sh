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

expect_state() {
  local label="$1"
  local expected="$2"
  shift 2
  local actual
  actual="$(classify_publication_state "$@")" || fail "$label unexpectedly failed"
  [[ "$actual" == "$expected" ]] || fail "$label expected state=$expected actual=$actual"
}

expect_state_failure() {
  local label="$1"
  shift
  if classify_publication_state "$@" >/dev/null 2>&1; then
    fail "$label unexpectedly produced an admissible state"
  fi
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

workflow="$repo_root/.github/workflows/publish-ksh-kanoon-articles.yml"
prepare_job="$tmp_dir/prepare-job.yml"
publish_job="$tmp_dir/publish-job.yml"
awk '/^  prepare:/{capture=1} /^  publish:/{capture=0} capture' "$workflow" > "$prepare_job"
awk '/^  publish:/{capture=1} capture' "$workflow" > "$publish_job"
[[ -s "$prepare_job" && -s "$publish_job" ]] || fail 'release workflow jobs could not be isolated for regression checks'

# workflow_dispatch input is untrusted shell data. It must cross the expression
# boundary through an environment variable and only then be syntax-validated.
input_refs="$(grep -Fc '${{ inputs.pr_number }}' "$workflow")"
[[ "$input_refs" == '1' ]] || fail 'PR number input must have exactly one expression reference'
grep -Fq 'PR_NUMBER: ${{ inputs.pr_number }}' "$prepare_job" || fail 'PR number input is not passed through env'
grep -Fq 'pr_number="$PR_NUMBER"' "$prepare_job" || fail 'PR number shell value is not read from env'

# Qualification/preparation owns repository-controlled execution and must be read-only.
grep -Fq 'contents: read' "$prepare_job" || fail 'prepare job does not declare contents: read'
grep -Fq 'actions: read' "$prepare_job" || fail 'prepare job does not declare actions: read'
grep -Fq 'pull-requests: read' "$prepare_job" || fail 'prepare job does not declare pull-requests: read'
if grep -Fq 'contents: write' "$prepare_job"; then
  fail 'prepare job must not have contents: write'
fi
for required in \
  'actions/checkout@' \
  'setup-php@' \
  'bash scripts/verify-foundation.sh' \
  'build-ksh-kanoon-articles.sh' \
  'verify-ksh-kanoon-articles-zip.sh'; do
  grep -Fq "$required" "$prepare_job" || fail "prepare job missing required qualification/build boundary: $required"
done
grep -Fq 'composer install' "$repo_root/scripts/verify-foundation.sh" || fail 'canonical verification no longer exercises Composer installation'

# Write authority is isolated to publication and consumes only the qualified handoff.
grep -Fq 'needs: prepare' "$publish_job" || fail 'publish job is not gated on successful prepare job completion'
grep -Fq 'contents: write' "$publish_job" || fail 'publish job does not declare contents: write'
if grep -Eq '^    env:' "$prepare_job" || grep -Eq '^    env:' "$publish_job"; then
  fail 'release job defines job-wide environment data; GitHub authentication must be step-scoped'
fi
for forbidden in \
  'actions/checkout@' \
  'setup-php@' \
  'verify-foundation.sh' \
  'composer ' \
  'build-ksh-kanoon-articles.sh' \
  'verify-ksh-kanoon-articles-zip.sh' \
  ' php ' \
  'php -l'; do
  if grep -Fq "$forbidden" "$publish_job"; then
    fail "write-authority publish job executes forbidden repository/dependency/PHP tooling: $forbidden"
  fi
done
if grep -Fq 'if: always()' "$publish_job"; then
  fail 'publish job must not bypass failed preparation with always()'
fi

# The immutable handoff must carry the exact pre-qualified artifact and identity.
grep -Fq 'actions/upload-artifact@043fb46d1a93c77aae656e7c1c64a875d1fc6a0a' "$prepare_job" || fail 'prepare job does not upload the immutable handoff with the pinned action'
grep -Fq 'actions/download-artifact@018cc2cf5baa6db3ef3c5f8a56943fffe632ef53' "$publish_job" || fail 'publish job does not download the qualified handoff with the pinned action'
grep -Fq 'artifact_sha256' "$prepare_job" || fail 'prepare handoff omits canonical artifact SHA-256'
grep -Fq 'actual_artifact_sha="$(sha256sum "$artifact"' "$publish_job" || fail 'publish job does not hash the handed-off canonical artifact'
grep -Fq 'actual_artifact_sha" != "$expected_artifact_sha' "$publish_job" || fail 'publish job does not bind the handed-off artifact to the qualified SHA-256'

# Existing candidate/integrated identities must remain fail closed.
grep -Fq 'candidate_tree' "$prepare_job" || fail 'candidate plugin-tree identity is missing'
grep -Fq 'integrated_tree' "$prepare_job" || fail 'integrated plugin-tree identity is missing'
grep -Fq 'candidate_tree" != "$integrated_tree' "$prepare_job" || fail 'plugin-tree mismatch does not fail closed'
grep -Fq 'candidate_notes_sha' "$prepare_job" || fail 'candidate release-note identity is missing'
grep -Fq 'integrated_notes_sha' "$prepare_job" || fail 'integrated release-note identity is missing'
grep -Fq 'candidate_notes_sha" != "$integrated_notes_sha' "$prepare_job" || fail 'release-note identity mismatch does not fail closed'

# Exercise the exact state-classification and mutation functions embedded in the workflow.
state_lib="$tmp_dir/publication-state-lib.sh"
: > "$state_lib"
extract_workflow_function 'BEGIN KSH_GITHUB_API_GET_FUNCTION' 'END KSH_GITHUB_API_GET_FUNCTION' "$state_lib"
extract_workflow_function 'BEGIN KSH_PUBLICATION_STATE_CLASSIFIER_FUNCTION' 'END KSH_PUBLICATION_STATE_CLASSIFIER_FUNCTION' "$state_lib"
extract_workflow_function 'BEGIN KSH_APPLY_PUBLICATION_STATE_FUNCTION' 'END KSH_APPLY_PUBLICATION_STATE_FUNCTION' "$state_lib"
[[ -s "$state_lib" ]] || fail 'publication-state test seam could not be extracted from workflow'
# shellcheck disable=SC1090
source "$state_lib"

expected_target='aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'
wrong_target='bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb'
expected_title='KSH Kanoon Articles v9.9.9'

expect_state 'absent state' 'ABSENT' \
  'NOT_FOUND' 'NOT_FOUND' '' "$expected_target" '' "$expected_title" '' '' 'false' 'MISSING'
expect_state 'matching tag-only state' 'TAG_ONLY_MATCHING' \
  'FOUND' 'NOT_FOUND' "$expected_target" "$expected_target" '' "$expected_title" '' '' 'false' 'MISSING'
expect_state_failure 'wrong-target tag-only state' \
  'FOUND' 'NOT_FOUND' "$wrong_target" "$expected_target" '' "$expected_title" '' '' 'false' 'MISSING'
expect_state 'matching Release missing asset' 'RELEASE_MATCHING_ASSET_MISSING' \
  'FOUND' 'FOUND' "$expected_target" "$expected_target" "$expected_title" "$expected_title" 'false' 'false' 'true' 'MISSING'
expect_state 'already complete matching publication' 'PUBLISHED_MATCHING' \
  'FOUND' 'FOUND' "$expected_target" "$expected_target" "$expected_title" "$expected_title" 'false' 'false' 'true' 'MATCHING'
expect_state_failure 'existing asset digest/content mismatch' \
  'FOUND' 'FOUND' "$expected_target" "$expected_target" "$expected_title" "$expected_title" 'false' 'false' 'true' 'MISMATCH'
expect_state_failure 'ambiguous uploaded asset set' \
  'FOUND' 'FOUND' "$expected_target" "$expected_target" "$expected_title" "$expected_title" 'false' 'false' 'true' 'AMBIGUOUS'
expect_state_failure 'Release metadata mismatch' \
  'FOUND' 'FOUND' "$expected_target" "$expected_target" 'Wrong title' "$expected_title" 'false' 'false' 'true' 'MISSING'
expect_state_failure 'Release-note mismatch' \
  'FOUND' 'FOUND' "$expected_target" "$expected_target" "$expected_title" "$expected_title" 'false' 'false' 'false' 'MISSING'
expect_state_failure 'Release without tag is ambiguous' \
  'NOT_FOUND' 'FOUND' '' "$expected_target" "$expected_title" "$expected_title" 'false' 'false' 'true' 'MISSING'

# GitHub API absence must be confirmed by HTTP 404; transport/server/auth uncertainty is failure.
mock_bin="$tmp_dir/mock-bin"
mkdir -p "$mock_bin"
cat > "$mock_bin/curl" <<'MOCK_CURL'
#!/usr/bin/env bash
set -euo pipefail
output=''
while (($#)); do
  case "$1" in
    --output)
      output="$2"
      shift 2
      ;;
    --write-out)
      shift 2
      ;;
    *)
      shift
      ;;
  esac
done
[[ -n "$output" ]] || exit 90
printf '%s' "${MOCK_CURL_BODY:-{}}" > "$output"
printf '%s' "${MOCK_CURL_HTTP_CODE:-200}"
exit "${MOCK_CURL_EXIT:-0}"
MOCK_CURL
chmod +x "$mock_bin/curl"

api_body="$tmp_dir/api.json"
api_state="$(PATH="$mock_bin:$PATH" GH_TOKEN='test-token' MOCK_CURL_HTTP_CODE=200 github_api_get 'https://api.example.invalid/found' "$api_body")" || fail 'mocked HTTP 200 state probe failed'
[[ "$api_state" == 'FOUND' ]] || fail "HTTP 200 should classify FOUND, got $api_state"
api_state="$(PATH="$mock_bin:$PATH" GH_TOKEN='test-token' MOCK_CURL_HTTP_CODE=404 github_api_get 'https://api.example.invalid/missing' "$api_body")" || fail 'mocked HTTP 404 state probe failed'
[[ "$api_state" == 'NOT_FOUND' ]] || fail "HTTP 404 should classify NOT_FOUND, got $api_state"
if PATH="$mock_bin:$PATH" GH_TOKEN='test-token' MOCK_CURL_HTTP_CODE=500 github_api_get 'https://api.example.invalid/server-error' "$api_body" >/dev/null 2>&1; then
  fail 'HTTP 500 must remain indeterminate rather than classify as absence'
fi
if PATH="$mock_bin:$PATH" GH_TOKEN='test-token' MOCK_CURL_HTTP_CODE=000 MOCK_CURL_EXIT=7 github_api_get 'https://api.example.invalid/transport-error' "$api_body" >/dev/null 2>&1; then
  fail 'transport failure must remain indeterminate rather than classify as absence'
fi

# Mutation decisions are also exercised with a stubbed gh CLI. No matching state may move a tag or clobber an asset.
cat > "$mock_bin/gh" <<'MOCK_GH'
#!/usr/bin/env bash
set -euo pipefail
printf '%s\n' "$*" >> "${MOCK_GH_LOG:?}"
exit "${MOCK_GH_EXIT:-0}"
MOCK_GH
chmod +x "$mock_bin/gh"

export GITHUB_REPOSITORY='rezahh107/KSH-Web'
notes_fixture="$tmp_dir/notes-fixture.md"
artifact_fixture="$tmp_dir/artifact-fixture.zip"
printf 'notes\n' > "$notes_fixture"
printf 'artifact\n' > "$artifact_fixture"

run_mutation_case() {
  local state="$1"
  local log="$2"
  : > "$log"
  PATH="$mock_bin:$PATH" MOCK_GH_LOG="$log" apply_publication_state \
    "$state" "$expected_target" 'ksh-kanoon-articles-v9.9.9' "$expected_title" "$notes_fixture" "$artifact_fixture"
}

absent_log="$tmp_dir/absent-gh.log"
run_mutation_case 'ABSENT' "$absent_log"
grep -Fq 'api --method POST repos/rezahh107/KSH-Web/git/refs' "$absent_log" || fail 'ABSENT state did not create the exact tag'
grep -Fq 'release create ksh-kanoon-articles-v9.9.9' "$absent_log" || fail 'ABSENT state did not create Release+asset'

tag_only_log="$tmp_dir/tag-only-gh.log"
run_mutation_case 'TAG_ONLY_MATCHING' "$tag_only_log"
if grep -Fq 'git/refs' "$tag_only_log"; then
  fail 'TAG_ONLY_MATCHING attempted to recreate/move the existing tag'
fi
grep -Fq 'release create ksh-kanoon-articles-v9.9.9' "$tag_only_log" || fail 'TAG_ONLY_MATCHING did not resume Release creation'

asset_missing_log="$tmp_dir/asset-missing-gh.log"
run_mutation_case 'RELEASE_MATCHING_ASSET_MISSING' "$asset_missing_log"
grep -Fq 'release upload ksh-kanoon-articles-v9.9.9' "$asset_missing_log" || fail 'matching Release missing asset did not resume exact asset upload'
if grep -Fq -- '--clobber' "$asset_missing_log"; then
  fail 'asset recovery must never overwrite an existing asset'
fi

published_log="$tmp_dir/published-gh.log"
run_mutation_case 'PUBLISHED_MATCHING' "$published_log"
[[ ! -s "$published_log" ]] || fail 'PUBLISHED_MATCHING should converge without mutation'

invalid_log="$tmp_dir/invalid-gh.log"
: > "$invalid_log"
if PATH="$mock_bin:$PATH" MOCK_GH_LOG="$invalid_log" apply_publication_state \
  'MISMATCH' "$expected_target" 'ksh-kanoon-articles-v9.9.9' "$expected_title" "$notes_fixture" "$artifact_fixture" >/dev/null 2>&1; then
  fail 'unsupported/mismatching state must not be mutated'
fi
[[ ! -s "$invalid_log" ]] || fail 'unsupported/mismatching state invoked GitHub mutation'

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

# Release notes need substantive content in every mandatory section.
valid_notes="$tmp_dir/valid-notes.md"
cat > "$valid_notes" <<'NOTES'
## What changed
- Bounded release-system test fixture.

## Real-host qualification
- NOT_PROVEN in this deterministic test.

## Evidence boundary
- This is test-only content.
NOTES
"$script_dir/verify-ksh-kanoon-articles-release-notes.sh" "$valid_notes" >/dev/null

headings_only="$tmp_dir/headings-only.md"
cat > "$headings_only" <<'NOTES'
## What changed

## Real-host qualification

## Evidence boundary
NOTES
expect_failure 'mandatory headings without substantive content' \
  "$script_dir/verify-ksh-kanoon-articles-release-notes.sh" "$headings_only"

template_copy="$tmp_dir/template-copy.md"
cp "$repo_root/docs/releases/ksh-kanoon-articles/TEMPLATE.md" "$template_copy"
expect_failure 'unchanged instructional template copy' \
  "$script_dir/verify-ksh-kanoon-articles-release-notes.sh" "$template_copy"

missing_boundary="$tmp_dir/missing-boundary.md"
cat > "$missing_boundary" <<'NOTES'
## What changed
- Change.

## Real-host qualification
- NOT_PROVEN.
NOTES
expect_failure 'missing evidence-boundary notes' \
  "$script_dir/verify-ksh-kanoon-articles-release-notes.sh" "$missing_boundary"

todo_notes="$tmp_dir/todo-notes.md"
cat > "$todo_notes" <<'NOTES'
## What changed
- TODO describe change.

## Real-host qualification
- NOT_PROVEN.

## Evidence boundary
- Boundary stated.
NOTES
expect_failure 'unresolved TODO/TBD release notes' \
  "$script_dir/verify-ksh-kanoon-articles-release-notes.sh" "$todo_notes"

printf 'KSH_RELEASE_SYSTEM_TEST_PASS version=%s\n' "$version"
