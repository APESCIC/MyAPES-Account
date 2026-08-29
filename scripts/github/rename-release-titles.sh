#!/usr/bin/env bash
set -euo pipefail

repo="${GITHUB_REPOSITORY:-APESCIC/MyAPES-Account}"
dry_run=false

usage() {
  cat <<'EOF'
Rename GitHub Release display titles to "{semver} Beta" (tags stay v{semver}).

Requires gh authenticated with permission to edit releases (contents: write).

Usage:
  bash scripts/github/rename-release-titles.sh [--dry-run]

Options:
  --dry-run   Print planned edits without calling gh release edit
EOF
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --dry-run)
      dry_run=true
      shift
      ;;
    -h|--help)
      usage
      exit 0
      ;;
    *)
      echo "Unknown option: $1" >&2
      usage >&2
      exit 1
      ;;
  esac
done

mapfile -t releases < <(
  gh release list --repo "$repo" --limit 500 --json tagName,name \
    --jq '.[] | [.tagName, .name] | @tsv'
)

if [[ ${#releases[@]} -eq 0 ]]; then
  echo "No releases found for ${repo}."
  exit 0
fi

updated=0
skipped=0

for entry in "${releases[@]}"; do
  tag="${entry%%$'\t'*}"
  current_name="${entry#*$'\t'}"

  if [[ ! "$tag" =~ ^v([0-9]+\.[0-9]+\.[0-9]+)$ ]]; then
    echo "Skipping non-semver tag: ${tag}"
    skipped=$((skipped + 1))
    continue
  fi

  version="${BASH_REMATCH[1]}"
  target_name="${version} Beta"

  if [[ "$current_name" == "$target_name" ]]; then
    echo "Already correct: ${tag} -> ${target_name}"
    skipped=$((skipped + 1))
    continue
  fi

  if [[ "$dry_run" == true ]]; then
    echo "Would edit ${tag}: \"${current_name}\" -> \"${target_name}\""
  else
    gh release edit "$tag" --repo "$repo" --title "$target_name"
    echo "Updated ${tag}: \"${current_name}\" -> \"${target_name}\""
  fi

  updated=$((updated + 1))
done

if [[ "$dry_run" == true ]]; then
  echo "Dry run complete: ${updated} release(s) would be updated, ${skipped} skipped."
else
  echo "Done: ${updated} release(s) updated, ${skipped} skipped."
fi
