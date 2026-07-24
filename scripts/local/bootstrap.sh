#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT_DIR"

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
php artisan migrate --force

echo "Local bootstrap complete."
