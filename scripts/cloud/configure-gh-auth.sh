#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT_DIR"

REPO="${GITHUB_REPOSITORY:-APESCIC/MyAPES-Account}"
GH_BIN="${GH_BIN:-gh}"

is_cloud_agent() {
  [[ -n "${CURSOR_CLOUD_AGENT:-}" || -n "${CURSOR_BC_ID:-}" || -d /exec-daemon ]]
}

if ! is_cloud_agent; then
  exit 0
fi

if [[ -z "${GH_ACTIONS_TOKEN:-}" ]]; then
  echo "Warning: GH_ACTIONS_TOKEN is not set. Cloud agents cannot dispatch workflows until this secret is added." >&2
  exit 0
fi

if ! command -v "$GH_BIN" >/dev/null 2>&1; then
  echo "Warning: gh CLI is not available for workflow auth verification." >&2
  exit 0
fi

workflow_count="$(GH_TOKEN="$GH_ACTIONS_TOKEN" "$GH_BIN" api "repos/${REPO}/actions/workflows" --jq '.total_count' 2>/dev/null || true)"

if [[ -z "$workflow_count" || "$workflow_count" == "null" ]]; then
  echo "Warning: GH_ACTIONS_TOKEN could not read workflows for ${REPO}. Check PAT scopes." >&2
  exit 0
fi

echo "GH_ACTIONS_TOKEN verified for ${REPO} (${workflow_count} workflow(s) visible)."
