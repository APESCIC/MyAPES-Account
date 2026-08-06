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

assert_runtime_ownership() {
  local protected_path=""
  local unexpected_owner=""

  unexpected_owner="$(find /app/data -xdev \
    -path /app/data/shared/storage -prune -o \
    -path '/app/data/releases/*/bootstrap/cache' -prune -o \
    ! -user root -print -quit)"
  if [[ -n "$unexpected_owner" ]]; then
    echo "Application runtime ownership is not root-controlled."
    return 1
  fi

  for protected_path in \
    /app/data \
    /app/data/releases \
    "$CURRENT_DIR" \
    "${CURRENT_DIR}/public" \
    /app/data/run.sh \
    /app/data/apache \
    /app/data/shared \
    /app/data/apache/app.conf; do
    if [[ ! -e "$protected_path" && ! -L "$protected_path" ]]; then
      echo "Protected runtime path is missing: $protected_path"
      return 1
    fi
    if [[ "$(stat -Lc '%U:%G' "$protected_path")" != "root:root" ]] \
      || sudo -u www-data test -w "$protected_path"; then
      echo "Application runtime ownership is not root-controlled."
      return 1
    fi
  done

  if [[ "$(stat -c '%U:%G' "$CURRENT_DIR")" != "root:root" \
    || ! -f /app/data/shared/.env \
    || -L /app/data/shared/.env \
    || "$(stat -Lc '%U:%G' /app/data/shared/.env)" != "root:www-data" ]] \
    || sudo -u www-data test -w /app/data/shared/.env; then
    echo "Application runtime ownership is not root-controlled."
    return 1
  fi

  if ! sudo -u www-data test -w "${CURRENT_DIR}/bootstrap/cache" \
    || ! sudo -u www-data test -w /app/data/shared/storage; then
    echo "Required Laravel runtime paths are not writable by the application user."
    return 1
  fi
}

restore_runtime_ownership() {
  local release_root=""
  local previous_target=""
  local -a release_roots=("$CURRENT_TARGET")

  find /app/data -xdev \
    -path /app/data/shared/storage -prune -o \
    -path '/app/data/releases/*/bootstrap/cache' -prune -o \
    -exec chown -h root:root {} +
  chown -hR www-data:www-data /app/data/shared/storage
  chown root:www-data /app/data/shared/.env
  chmod 0640 /app/data/shared/.env
  chmod 0555 /app/data /app/data/releases /app/data/shared /app/data/apache
  chmod 0555 /app/data/run.sh
  chmod 0444 /app/data/apache/app.conf

  if [[ -L /app/data/previous ]]; then
    previous_target="$(readlink -f /app/data/previous)"
    [[ "$previous_target" =~ ^/app/data/releases/[0-9a-f]{40}$ ]]
    [[ "$previous_target" != "$CURRENT_TARGET" ]]
    release_roots+=("$previous_target")
  fi

  for release_root in "${release_roots[@]}"; do
    [[ "$release_root" =~ ^/app/data/releases/[0-9a-f]{40}$ ]]
    chmod -R a-w "$release_root"
    install -d -o www-data -g www-data -m 0770 "$release_root/bootstrap/cache"
    chown -hR www-data:www-data "$release_root/bootstrap/cache"
    chmod -R u+rwX,g+rwX,o-rwx "$release_root/bootstrap/cache"
    chmod 0555 "$release_root" "$release_root/public"
  done

  assert_runtime_ownership
}

start_laravel_runtime() {
  install -d -o www-data -g www-data -m 0775 "$LOG_DIR"
  touch "${LOG_DIR}/queue-worker.log" "${LOG_DIR}/scheduler.log"
  chown www-data:www-data "${LOG_DIR}/queue-worker.log" "${LOG_DIR}/scheduler.log"

  run_artisan config:clear
  run_artisan config:cache
  run_artisan route:cache
  run_artisan view:cache

  if ! run_artisan env --no-ansi | grep -Eq 'production'; then
    echo "Effective Laravel environment verification failed."
    return 1
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
}

assert_runtime_ownership
MYAPES_OWNERSHIP_RESTORED=false

chown() {
  local touches_app_data=false
  local argument=""

  for argument in "$@"; do
    if [[ "$argument" == "/app/data" ]]; then
      touches_app_data=true
      break
    fi
  done

  command chown "$@"
  if [[ "$touches_app_data" == false ]]; then
    return
  fi

  if [[ "$#" -ne 6 || "$1" != "-R" || "$2" != "www-data:www-data" \
    || "$3" != "/app/data" || "$4" != "/run/apache2" \
    || "$5" != "/run/app" || "$6" != "/tmp" ]]; then
    echo "Unsupported Cloudron ownership-normalization contract."
    return 1
  fi

  unset -f chown
  restore_runtime_ownership
  start_laravel_runtime
  MYAPES_OWNERSHIP_RESTORED=true
}

exec() {
  if [[ "$MYAPES_OWNERSHIP_RESTORED" != true ]]; then
    echo "Refusing process replacement before root ownership restoration."
    return 1
  fi

  unset -f exec
  builtin exec "$@"
}
