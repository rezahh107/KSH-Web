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

# Existing candidate/integrated identity and conflict gates must remain fail closed.
grep -Fq 'candidate_tree' "$prepare_job" || fail 'candidate plugin-tree identity is missing'
grep -Fq 'integrated_tree' "$prepare_job" || fail 'integrated plugin-tree identity is missing'
grep -Fq 'candidate_tree" != "$integrated_tree' "$prepare_job" || fail 'plugin-tree mismatch does not fail closed'
grep -Fq 'candidate_notes_sha' "$prepare_job" || fail 'candidate release-note identity is missing'
grep -Fq 'integrated_notes_sha' "$prepare_job" || fail 'integrated release-note identity is missing'
grep -Fq 'candidate_notes_sha" != "$integrated_notes_sha' "$prepare_job" || fail 'release-note identity mismatch does not fail closed'
grep -Fq 'git/ref/tags/${tag}' "$publish_job" || fail 'publish job does not recheck tag conflict immediately before mutation'
grep -Fq 'releases/tags/${tag}' "$publish_job" || fail 'publish job does not recheck Release conflict immediately before mutation'

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
