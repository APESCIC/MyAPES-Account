#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT_DIR"

RUN_SEED=false
RUN_FRESH=false

while [[ $# -gt 0 ]]; do
  case "$1" in
    --seed)
      RUN_SEED=true
      shift
      ;;
    --fresh)
      RUN_FRESH=true
      RUN_SEED=true
      shift
      ;;
    *)
      echo "Unknown option: $1"
      echo "Usage: bash scripts/local/bootstrap.sh [--seed] [--fresh]"
      exit 1
      ;;
  esac
done

for tool in php composer npm; do
  if ! command -v "$tool" >/dev/null 2>&1; then
    echo "Missing required tool: $tool"
    exit 1
  fi
done

if [[ ! -f artisan ]]; then
  echo "artisan was not found in $ROOT_DIR. Run this inside the Laravel project root."
  exit 1
fi

if [[ ! -f composer.json ]]; then
  echo "composer.json was not found in $ROOT_DIR. Run this inside the Laravel project root."
  exit 1
fi

if [[ ! -f .env ]]; then
  if [[ -f .env.example ]]; then
    cp .env.example .env
    echo "Created .env from .env.example"
  else
    echo "Neither .env nor .env.example exists."
    exit 1
  fi
fi

composer install --no-interaction --prefer-dist
npm install --no-audit --no-fund
php artisan key:generate --force

if [[ "$RUN_FRESH" == true ]]; then
  echo "Running destructive local QA reset (migrate:fresh --seed)."
  php artisan migrate:fresh --seed --force
elif [[ "$RUN_SEED" == true ]]; then
  php artisan migrate --force
  php artisan db:seed --force
else
  php artisan migrate --force
fi

echo "Local bootstrap complete."
