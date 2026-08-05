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
