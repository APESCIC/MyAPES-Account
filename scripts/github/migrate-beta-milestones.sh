#!/usr/bin/env bash
set -euo pipefail

# Rename product milestones to v0.N.x Beta and ensure closed historical
# ≤0.30 minor-line milestones exist from releases.json.
#
# Usage:
#   bash scripts/github/migrate-beta-milestones.sh [path/to/releases.json]

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
  local state="${4:-closed}"
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

# Product milestones #1–#9 → v0.N.x Beta (all completed through Account security).
# Number order follows GitHub creation order, not semver order.
rename_milestone 1 "v0.31.x Beta" \
  "Closed: v1.0.0 Beta stack complete on live (Access/RBAC, password pack, changelog guest filter, stale Super Admin redirects)." \
  closed

rename_milestone 2 "v0.32.x Beta" \
  "Closed: v1.1.0 Beta Public UX & compliance (branded errors, UK dates, privacy/terms, Core vs Account naming)." \
  closed

rename_milestone 3 "v0.33.x Beta" \
  "Closed: v1.2.0 Beta Staff UX (hub URLs, empty states, Admin KPI cleanup, staff login chase, groups sync)." \
  closed

rename_milestone 4 "v0.34.x Beta" \
  "Closed: v1.3.0 Beta Recruitment plugin (public roles board, APES CIC manage, applications workflow)." \
  closed

rename_milestone 5 "v0.39.x Beta" \
  "Closed: v1.8.0 Beta Account security (auth emails, step-up, password/username/verify hardening, secure email change, TOTP, passkeys, PHPUnit matrix)." \
  closed

rename_milestone 6 "v0.35.x Beta" \
  "Closed: v1.4.0 Beta Unified Admin shell + plugin settings (Admin submenu, Super Admin redirects, plugin settings registry)." \
  closed

rename_milestone 7 "v0.36.x Beta" \
  "Closed: v1.5.0 Beta Recruitment dual-frontend IA (public Open roles / My applications; staff Recruit manage)." \
  closed

rename_milestone 8 "v0.38.x Beta" \
  "Closed: v1.7.0 Beta Language & keywords (en_GB primary, glossary, plugin namespaces, locale preference, lang:check CI)." \
  closed

rename_milestone 9 "v0.37.x Beta" \
  "Closed: v1.6.0 Beta Structure — Core > Modules > Plugins (ADR 0001, package layout, architecture hard-fail, generators)." \
  closed

echo "Milestone migration complete."
