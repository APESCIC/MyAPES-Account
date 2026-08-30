#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT_DIR"

if command -v pwsh >/dev/null 2>&1; then
  exec pwsh -NoProfile -ExecutionPolicy Bypass -File scripts/local/pre-pr-verify.ps1 "$@"
fi

if command -v powershell >/dev/null 2>&1; then
  exec powershell -NoProfile -ExecutionPolicy Bypass -File scripts/local/pre-pr-verify.ps1 "$@"
fi

echo "Running pre-merge contract gate..."
composer pre-merge

bootstrap_args=(--seed)
while [[ $# -gt 0 ]]; do
  case "$1" in
    --fresh|-Fresh)
      bootstrap_args=(--fresh)
      shift
      ;;
    --seed|-Seed)
      bootstrap_args=(--seed)
      shift
      ;;
    *)
      echo "Unknown option: $1"
      echo "Usage: bash scripts/local/pre-pr-verify.sh [--seed|--fresh]"
      exit 1
      ;;
  esac
done

echo "Running local bootstrap..."
bash scripts/local/bootstrap.sh "${bootstrap_args[@]}"

version="$(tr -d '\r\n' < VERSION)"
base_url="http://127.0.0.1:8000"

echo ""
echo "Pre-PR local verify checks passed."
echo "VERSION: v${version}"
echo "Smoke healthz: ${base_url}/healthz"
echo "Smoke change-log: ${base_url}/change-log"
echo ""
echo "Start the stack if needed: composer run dev"
echo "Then spot-check changed routes in the browser before commit/PR."
