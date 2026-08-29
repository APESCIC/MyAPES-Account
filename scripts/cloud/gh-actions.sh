#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT_DIR"

GH_BIN="${GH_BIN:-gh}"
REPO="${GITHUB_REPOSITORY:-APESCIC/MyAPES-Account}"

usage() {
  cat <<'USAGE'
Usage:
  bash scripts/cloud/gh-actions.sh workflow-run <workflow-name> <ref> [input=value ...]
  bash scripts/cloud/gh-actions.sh run-rerun <run-id>
  bash scripts/cloud/gh-actions.sh run-watch <run-id>

Cloud agents need GH_ACTIONS_TOKEN in Cursor environment secrets for write operations.
Read-only commands (gh run list, gh pr checks) can use the default gh auth.
USAGE
}

is_cloud_agent() {
  [[ -n "${CURSOR_CLOUD_AGENT:-}" || -n "${CURSOR_BC_ID:-}" || -d /exec-daemon ]]
}

require_actions_token() {
  if [[ -n "${GH_ACTIONS_TOKEN:-}" ]]; then
    return 0
  fi

  if is_cloud_agent; then
    echo "GH_ACTIONS_TOKEN is not set. Add a fine-grained GitHub PAT with Actions write access to Cursor environment secrets." >&2
    exit 1
  fi

  return 0
}

gh_with_actions_token() {
  require_actions_token

  if [[ -n "${GH_ACTIONS_TOKEN:-}" ]]; then
    GH_TOKEN="$GH_ACTIONS_TOKEN" "$GH_BIN" "$@"
    return
  fi

  "$GH_BIN" "$@"
}

workflow_run() {
  local workflow_name="${1:-}"
  local ref="${2:-}"

  if [[ -z "$workflow_name" || -z "$ref" ]]; then
    echo "workflow-run requires <workflow-name> and <ref>." >&2
    usage
    exit 1
  fi

  shift 2

  local -a field_args=()
  local input

  for input in "$@"; do
    if [[ "$input" != *"="* ]]; then
      echo "Invalid workflow input (expected key=value): $input" >&2
      exit 1
    fi

    field_args+=(-f "$input")
  done

  gh_with_actions_token workflow run "$workflow_name" --repo "$REPO" --ref "$ref" "${field_args[@]}"
}

run_rerun() {
  local run_id="${1:-}"

  if [[ -z "$run_id" ]]; then
    echo "run-rerun requires <run-id>." >&2
    usage
    exit 1
  fi

  gh_with_actions_token run rerun "$run_id" --repo "$REPO"
}

run_watch() {
  local run_id="${1:-}"

  if [[ -z "$run_id" ]]; then
    echo "run-watch requires <run-id>." >&2
    usage
    exit 1
  fi

  "$GH_BIN" run watch "$run_id" --repo "$REPO" --exit-status
}

main() {
  local command="${1:-}"

  if [[ -z "$command" ]]; then
    usage
    exit 1
  fi

  shift

  case "$command" in
    workflow-run)
      workflow_run "$@"
      ;;
    run-rerun)
      run_rerun "$@"
      ;;
    run-watch)
      run_watch "$@"
      ;;
    -h|--help|help)
      usage
      ;;
    *)
      echo "Unknown command: $command" >&2
      usage
      exit 1
      ;;
  esac
}

main "$@"
