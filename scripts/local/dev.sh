#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT_DIR"

if [[ ! -f artisan ]]; then
  echo "artisan was not found in $ROOT_DIR. Run this inside the Laravel project root."
  exit 1
fi

if [[ ! -f composer.json ]]; then
  echo "composer.json was not found in $ROOT_DIR. Run this inside the Laravel project root."
  exit 1
fi

if composer run --no-ansi 2>/dev/null | grep -qE '^\s*dev(\s|$)'; then
  composer run dev
else
  echo "No composer 'dev' script found. Starting Laravel server only."
  php artisan serve --host=127.0.0.1 --port="${APP_PORT:-8000}"
fi
