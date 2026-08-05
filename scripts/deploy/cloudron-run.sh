#!/usr/bin/env bash
set -euo pipefail

CURRENT_DIR="/app/data/current"
PHP_BIN="/usr/bin/php8.4"
LOG_DIR="/app/data/shared/storage/logs"
export APP_ENV=production

run_artisan() {
  sudo -E -u www-data env APP_ENV=production \
    "$PHP_BIN" "${CURRENT_DIR}/artisan" "$@"
}

if [[ ! -f "${CURRENT_DIR}/artisan" ]]; then
  echo "MyAPES release is not active yet; skipping Laravel workers."
  exit 0
fi

CURRENT_TARGET="$(readlink -f "$CURRENT_DIR" 2>/dev/null || true)"
if [[ ! "$CURRENT_TARGET" =~ ^/app/data/releases/[0-9a-f]{40}$ \
  || ! -d "$CURRENT_TARGET" ]]; then
  echo "Current release does not resolve to an immutable release directory."
  exit 1
fi

for protected_path in \
  /app/data \
  /app/data/releases \
  "$CURRENT_DIR" \
  "${CURRENT_DIR}/public" \
  /app/data/run.sh \
  /app/data/apache/app.conf; do
  if [[ ! -e "$protected_path" && ! -L "$protected_path" ]]; then
    echo "Protected runtime path is missing: $protected_path"
    exit 1
  fi
  if sudo -u www-data test -w "$protected_path"; then
    echo "Application user can replace a protected runtime path: $protected_path"
    exit 1
  fi
done

if ! sudo -u www-data test -w "${CURRENT_DIR}/bootstrap/cache" \
  || ! sudo -u www-data test -w /app/data/shared/storage; then
  echo "Required Laravel runtime paths are not writable by the application user."
  exit 1
fi

install -d -o www-data -g www-data -m 0775 "$LOG_DIR"
touch "${LOG_DIR}/queue-worker.log" "${LOG_DIR}/scheduler.log"
chown www-data:www-data "${LOG_DIR}/queue-worker.log" "${LOG_DIR}/scheduler.log"

run_artisan config:clear
run_artisan config:cache
run_artisan route:cache
run_artisan view:cache

if ! run_artisan env --no-ansi | grep -Eq 'production'; then
  echo "Effective Laravel environment verification failed."
  exit 1
fi

if ! pgrep -f "${CURRENT_DIR}/artisan queue:work" >/dev/null 2>&1; then
  run_artisan queue:work \
    --sleep=3 \
    --tries=3 \
    --timeout=60 \
    >>"${LOG_DIR}/queue-worker.log" 2>&1 &
fi

if ! pgrep -f "${CURRENT_DIR}/artisan schedule:work" >/dev/null 2>&1; then
  run_artisan schedule:work \
    >>"${LOG_DIR}/scheduler.log" 2>&1 &
fi
