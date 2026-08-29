#!/usr/bin/env bash
set -euo pipefail

repo="${GITHUB_REPOSITORY:-APESCIC/MyAPES-Account}"
releases_file="${1:-resources/data/releases.json}"

if [[ ! -f "$releases_file" ]]; then
  echo "Release history file not found: $releases_file" >&2
  exit 1
fi

milestone_exists() {
  local title="$1"
  gh api "repos/${repo}/milestones" --paginate \
    --jq ".[] | select(.title == \"${title}\") | .number" | head -n1
}

ensure_closed_milestone() {
  local title="$1"
  local description="$2"
  local existing
  existing="$(milestone_exists "$title" || true)"
  if [[ -n "$existing" ]]; then
    gh api -X PATCH "repos/${repo}/milestones/${existing}" \
      -f state=closed \
      -f description="$description" >/dev/null
    echo "Updated closed milestone #${existing}: ${title}"
    return
  fi

  local number
  number="$(gh api "repos/${repo}/milestones" \
    -f title="$title" \
    -f state=closed \
    -f description="$description" \
    --jq '.number')"
  echo "Created closed milestone #${number}: ${title}"
}

rename_milestone() {
  local number="$1"
  local title="$2"
  local description="$3"
  local state="${4:-open}"
  gh api -X PATCH "repos/${repo}/milestones/${number}" \
    -f title="$title" \
    -f state="$state" \
    -f description="$description" >/dev/null
  echo "Renamed milestone #${number} to ${title} (${state})"
}

mapfile -t minor_lines < <(
  jq -r '
    [.[].version | split(".") | .[0:2] | join(".")]
    | unique
    | sort_by(split(".") | map(tonumber))
    | .[]
  ' "$releases_file"
)

for minor_line in "${minor_lines[@]}"; do
  major="${minor_line%%.*}"
  minor="${minor_line#*.}"
  if [[ "$major" == "0" && "$minor" -le 30 ]]; then
    versions="$(jq -r --arg line "$minor_line" '
      [.[] | select(.version | startswith($line + ".")) | .version]
      | unique
      | sort_by(split(".") | map(tonumber))
      | join(", ")
    ' "$releases_file")"
    ensure_closed_milestone \
      "v${minor_line}.x Beta" \
      "Completed minor-line releases: ${versions}"
  fi
done

rename_milestone 1 "v0.31.x Beta" \
  "Closed: v1.0.0 Beta stack complete on live (Access/RBAC, password pack, changelog guest filter, stale Super Admin redirects)." \
  closed

rename_milestone 2 "v0.32.x Beta" \
  "Active: v1.1.0 Beta Public UX & compliance backlog. Patch releases stay on this milestone until v0.33.0 ships." \
  open

rename_milestone 3 "v0.33.x Beta" \
  "Planned: v1.2.0 Beta Staff UX backlog. Start when v0.32.x work is underway or complete." \
  open

echo "Milestone migration complete."
