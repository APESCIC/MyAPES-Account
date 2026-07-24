#!/usr/bin/env bash
set -euo pipefail

CURRENT_DIR="/app/data/current"
PHP_BIN="/usr/bin/php8.4"
LOG_DIR="/app/data/shared/storage/logs"

if [[ ! -f "${CURRENT_DIR}/artisan" ]]; then
  echo "MyAPES release is not active yet; skipping Laravel workers."
  exit 0
fi

install -d -o www-data -g www-data -m 0775 "$LOG_DIR"
touch "${LOG_DIR}/queue-worker.log" "${LOG_DIR}/scheduler.log"
chown www-data:www-data "${LOG_DIR}/queue-worker.log" "${LOG_DIR}/scheduler.log"

sudo -E -u www-data "$PHP_BIN" "${CURRENT_DIR}/artisan" config:clear
sudo -E -u www-data "$PHP_BIN" "${CURRENT_DIR}/artisan" config:cache
sudo -E -u www-data "$PHP_BIN" "${CURRENT_DIR}/artisan" route:cache
sudo -E -u www-data "$PHP_BIN" "${CURRENT_DIR}/artisan" view:cache

if ! pgrep -f "${CURRENT_DIR}/artisan queue:work" >/dev/null 2>&1; then
  sudo -E -u www-data "$PHP_BIN" "${CURRENT_DIR}/artisan" queue:work \
    --sleep=3 \
    --tries=3 \
    --timeout=90 \
    >>"${LOG_DIR}/queue-worker.log" 2>&1 &
fi

if ! pgrep -f "${CURRENT_DIR}/artisan schedule:work" >/dev/null 2>&1; then
  sudo -E -u www-data "$PHP_BIN" "${CURRENT_DIR}/artisan" schedule:work \
    >>"${LOG_DIR}/scheduler.log" 2>&1 &
fi
