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

for required_file in artisan composer.json package.json .env.local.example; do
  if [[ ! -f "$required_file" ]]; then
    echo "Required bootstrap file is missing: $required_file"
    exit 1
  fi
done

if [[ ! -f .env ]]; then
  cp .env.local.example .env
  php artisan key:generate --force --no-interaction
  echo "Created .env from .env.local.example"
fi

SQLITE_PATH="$ROOT_DIR/database/database.sqlite"
mkdir -p "$(dirname "$SQLITE_PATH")"
touch "$SQLITE_PATH"

if [[ -d vendor && -f vendor/autoload.php && vendor -nt composer.lock ]]; then
  echo "Composer dependencies already installed."
else
  composer install --no-interaction --prefer-dist
fi

if [[ -d node_modules && node_modules -nt package-lock.json ]]; then
  echo "Node dependencies already installed."
else
  npm ci --no-audit --no-fund
fi

chmod +x scripts/cloud/gh-actions.sh scripts/cloud/configure-gh-auth.sh

echo "Cloud agent install completed."
