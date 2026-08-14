#!/usr/bin/env bash
set -euo pipefail

deployment_control_paths=(
  scripts/deploy/activate-release.sh
  scripts/deploy/rollback-release.sh
  scripts/deploy/cloudron-app.conf
  scripts/deploy/cloudron-run.sh
)

verify_deployment_controls() {
  local source_root="${1:-}"
  local expected_manifest_sha256="${2:-}"
  local manifest_path="${source_root}/DEPLOYMENT-CONTROLS.sha256"
  local actual_manifest_sha256=""
  local index=0
  local line=""
  local digest=""
  local manifest_control_path=""
  local expected_path=""
  local -a manifest_lines=()

  if [[ -z "$source_root" || ! "$expected_manifest_sha256" =~ ^[0-9a-f]{64}$ ]]; then
    echo "Deployment control verification requires a source root and exact manifest SHA-256."
    return 1
  fi
  if [[ ! -f "$manifest_path" || -L "$manifest_path" ]]; then
    echo "Deployment control manifest is missing or unsafe."
    return 1
  fi

  actual_manifest_sha256="$(sha256sum "$manifest_path" | awk '{print $1}')"
  if [[ "$actual_manifest_sha256" != "$expected_manifest_sha256" ]]; then
    echo "Deployment control manifest digest does not match the tested artifact."
    return 1
  fi

  mapfile -t manifest_lines <"$manifest_path"
  if [[ "${#manifest_lines[@]}" -ne "${#deployment_control_paths[@]}" ]]; then
    echo "Deployment control manifest has an unexpected entry count."
    return 1
  fi

  for index in "${!deployment_control_paths[@]}"; do
    expected_path="${deployment_control_paths[$index]}"
    line="${manifest_lines[$index]}"
    digest="${line%%  *}"
    manifest_control_path="${line#*  }"
    if [[ ! "$digest" =~ ^[0-9a-f]{64}$ \
      || "$manifest_control_path" != "$expected_path" \
      || "$line" != "$digest  $expected_path" ]]; then
      echo "Deployment control manifest contains an unexpected entry."
      return 1
    fi
    if [[ ! -f "${source_root}/${expected_path}" || -L "${source_root}/${expected_path}" ]]; then
      echo "Deployment control path is missing or unsafe: $expected_path"
      return 1
    fi
  done

  if ! (cd "$source_root" && sha256sum --check --strict --status DEPLOYMENT-CONTROLS.sha256); then
    echo "Deployment control content authentication failed."
    return 1
  fi
}

assert_hardened_control_directory() {
  local source_root="${1:-}"
  local path=""
  local expected_mode=""

  if [[ ! -d "$source_root" || -L "$source_root" \
    || "$(stat -c '%U:%G' "$source_root")" != "root:root" \
    || "$(stat -c '%a' "$source_root")" != "700" ]]; then
    echo "Deployment control directory is not root-owned and private."
    return 1
  fi

  while IFS=':' read -r path expected_mode; do
    if [[ ! -f "${source_root}/${path}" \
      || -L "${source_root}/${path}" \
      || "$(stat -c '%U:%G' "${source_root}/${path}")" != "root:root" \
      || "$(stat -c '%a' "${source_root}/${path}")" != "$expected_mode" ]]; then
      echo "Deployment control path is not root-owned and immutable: $path"
      return 1
    fi
  done <<'CONTROL_MODES'
DEPLOYMENT-CONTROLS.sha256:600
scripts/deploy/activate-release.sh:700
scripts/deploy/rollback-release.sh:700
scripts/deploy/cloudron-app.conf:600
scripts/deploy/cloudron-run.sh:700
CONTROL_MODES

  if sudo -u www-data test -w "$source_root"; then
    echo "Application user can replace authenticated deployment controls."
    return 1
  fi
}

assert_release_root_ownership() {
  local release_root="${1:-}"
  local unexpected_owner=""

  if [[ -z "$release_root" || ! -d "$release_root" ]]; then
    echo "Release ownership verification requires an existing release directory."
    return 1
  fi

  unexpected_owner="$(find "$release_root" -xdev \
    ! -user root \
    ! -path "${release_root}/bootstrap/cache" \
    ! -path "${release_root}/bootstrap/cache/*" \
    -print -quit)"
  if [[ -n "$unexpected_owner" \
    || "$(stat -Lc '%U:%G' "$release_root")" != "root:root" \
    || "$(stat -Lc '%U:%G' "${release_root}/public")" != "root:root" ]]; then
    echo "Release ownership is not root-controlled."
    return 1
  fi
}

SELECTIVE_MEDIA_MARKER_NAME=".myapes-selective-media"
SELECTIVE_MEDIA_MARKER_CONTENT="myapes-selective-media:v1"

assert_canonical_deployment_directory() {
  local path="${1:-}"
  local description="${2:-deployment directory}"
  local required="${3:-false}"
  local canonical_path=""

  if [[ -z "$path" || -L "$path" ]]; then
    echo "Unsafe canonical deployment path for ${description}: ${path}"
    return 1
  fi
  if [[ -e "$path" ]]; then
    if [[ ! -d "$path" ]]; then
      echo "Unsafe canonical deployment path for ${description}: ${path}"
      return 1
    fi
    canonical_path="$(readlink -f -- "$path" 2>/dev/null || true)"
  else
    if [[ "$required" == true ]]; then
      echo "Required canonical deployment path is missing for ${description}: ${path}"
      return 1
    fi
    canonical_path="$(readlink -m -- "$path" 2>/dev/null || true)"
  fi
  if [[ -z "$canonical_path" || "$canonical_path" != "$path" ]]; then
    echo "Unsafe canonical deployment path for ${description}: ${path}"
    return 1
  fi
}

assert_canonical_deployment_file() {
  local path="${1:-}"
  local description="${2:-deployment file}"
  local required="${3:-false}"
  local canonical_path=""

  if [[ -z "$path" || -L "$path" ]]; then
    echo "Unsafe canonical deployment file for ${description}: ${path}"
    return 1
  fi
  if [[ -e "$path" ]]; then
    if [[ ! -f "$path" ]]; then
      echo "Unsafe canonical deployment file for ${description}: ${path}"
      return 1
    fi
    canonical_path="$(readlink -f -- "$path" 2>/dev/null || true)"
  else
    if [[ "$required" == true ]]; then
      echo "Required canonical deployment file is missing for ${description}: ${path}"
      return 1
    fi
    canonical_path="$(readlink -m -- "$path" 2>/dev/null || true)"
  fi
  if [[ -z "$canonical_path" || "$canonical_path" != "$path" ]]; then
    echo "Unsafe canonical deployment file for ${description}: ${path}"
    return 1
  fi
}

assert_rollback_path_boundaries() {
  assert_canonical_deployment_directory "$DATA_DIR" "application data root" true
  assert_canonical_deployment_directory "${DATA_DIR}/apache" "Apache runtime parent" true
  assert_canonical_deployment_file \
    "${DATA_DIR}/apache/app.conf" "Apache runtime configuration" true
  assert_canonical_deployment_file \
    "${DATA_DIR}/apache/app.conf.rollback" "staged Apache runtime configuration"
  assert_canonical_deployment_file "${DATA_DIR}/run.sh" "Cloudron runtime launcher" true
  assert_canonical_deployment_file \
    "${DATA_DIR}/run.sh.rollback" "staged Cloudron runtime launcher"
  assert_canonical_deployment_directory "$RELEASES_DIR" "release parent" true
  assert_canonical_deployment_directory "$SHARED_DIR" "shared parent" true
  assert_canonical_deployment_file \
    "${SHARED_DIR}/.env" "shared environment" true
  assert_canonical_deployment_directory "${SHARED_DIR}/storage" "shared storage" true
  assert_canonical_deployment_directory \
    "${SHARED_DIR}/storage/app" "shared storage app" true
  assert_canonical_deployment_directory \
    "${SHARED_DIR}/storage/app/public" "shared public storage" true
  assert_canonical_deployment_directory \
    "${SHARED_DIR}/storage/app/public/avatars" "shared avatars storage" true
}

assert_release_runtime_path_boundaries() {
  local release_root="${1:-}"
  local cache_required="${2:-false}"
  local path=""
  local canonical_path=""
  local -a required_directories=(
    "$release_root"
    "${release_root}/public"
    "${release_root}/bootstrap"
  )

  for path in "${required_directories[@]}"; do
    if [[ -z "$release_root" || ! -d "$path" || -L "$path" ]]; then
      echo "Unsafe release runtime path: $path"
      return 1
    fi
    canonical_path="$(readlink -f -- "$path" 2>/dev/null || true)"
    if [[ -z "$canonical_path" || "$canonical_path" != "$path" ]]; then
      echo "Unsafe release runtime path: $path"
      return 1
    fi
  done

  path="${release_root}/bootstrap/cache"
  if [[ -L "$path" ]]; then
    echo "Unsafe release runtime path: $path"
    return 1
  fi
  if [[ -e "$path" ]]; then
    if [[ ! -d "$path" ]]; then
      echo "Unsafe release runtime path: $path"
      return 1
    fi
    canonical_path="$(readlink -f -- "$path" 2>/dev/null || true)"
  else
    if [[ "$cache_required" == true ]]; then
      echo "Unsafe release runtime path: $path"
      return 1
    fi
    canonical_path="$(readlink -m -- "$path" 2>/dev/null || true)"
  fi
  if [[ -z "$canonical_path" || "$canonical_path" != "$path" ]]; then
    echo "Unsafe release runtime path: $path"
    return 1
  fi
}

install_public_storage_boundary() {
  local release_root="${1:-}"
  local public_root="${release_root}/public"
  local public_storage="${public_root}/storage"
  local shared_public_storage="${SHARED_DIR}/storage/app/public"
  local marker="${public_storage}/${SELECTIVE_MEDIA_MARKER_NAME}"
  local avatars_link="${public_storage}/avatars"
  local shared_avatars="${shared_public_storage}/avatars"
  local unexpected_entry=""
  local version=""
  local major=""
  local minor=""
  local patch=""
  local selective_required=false

  if [[ -z "$release_root" \
    || "$release_root" != "${RELEASES_DIR}/"* \
    || ! -d "$release_root" \
    || ! -d "$public_root" \
    || -L "$public_root" ]]; then
    echo "Public storage boundary requires a valid immutable release directory."
    return 1
  fi

  version="$(tr -d '\r\n' <"${release_root}/VERSION" 2>/dev/null || true)"
  if [[ ! "$version" =~ ^(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)$ ]]; then
    echo "Public storage boundary requires a valid release VERSION."
    return 1
  fi
  major="${BASH_REMATCH[1]}"
  minor="${BASH_REMATCH[2]}"
  patch="${BASH_REMATCH[3]}"
  if [[ "$major" != 0 \
    || ${#minor} -gt 2 \
    || (${#minor} -eq 2 && "$minor" > 13) ]]; then
    selective_required=true
  fi
  if [[ ! -d "$shared_public_storage" || -L "$shared_public_storage" ]]; then
    echo "Shared public storage target is missing or unsafe."
    return 1
  fi
  shared_public_storage="$(readlink -f "$shared_public_storage")"
  shared_avatars="${shared_public_storage}/avatars"

  if [[ -L "$public_storage" ]]; then
    if [[ "$selective_required" == true ]]; then
      echo "Selective-media release cannot use the legacy public storage link."
      return 1
    fi
    if [[ "$(readlink -f "$public_storage" 2>/dev/null || true)" != "$shared_public_storage" ]]; then
      echo "Legacy public storage link targets an unexpected path."
      return 1
    fi
  elif [[ -d "$public_storage" ]]; then
    if [[ "$selective_required" != true ]]; then
      echo "Legacy release cannot use the selective public storage directory."
      return 1
    fi
    if [[ ! -f "$marker" || -L "$marker" ]] \
      || ! cmp -s "$marker" <(printf '%s\n' "$SELECTIVE_MEDIA_MARKER_CONTENT"); then
      echo "Selective-media marker is missing, unsafe, or tampered."
      return 1
    fi
    unexpected_entry="$(find "$public_storage" -mindepth 1 -maxdepth 1 \
      ! -name "$SELECTIVE_MEDIA_MARKER_NAME" ! -name avatars -print -quit)"
    if [[ -n "$unexpected_entry" ]]; then
      echo "Unexpected public storage entry: $unexpected_entry"
      return 1
    fi
    if [[ ! -d "$shared_avatars" || -L "$shared_avatars" ]]; then
      echo "Shared avatars target is missing or unsafe."
      return 1
    fi
    if [[ -L "$avatars_link" ]]; then
      if [[ "$(readlink -f "$avatars_link" 2>/dev/null || true)" != "$shared_avatars" ]]; then
        echo "Avatar storage link targets an unexpected path."
        return 1
      fi
    elif [[ -e "$avatars_link" ]]; then
      echo "Avatar storage path exists and is not the expected symlink."
      return 1
    else
      ln -s "$shared_avatars" "$avatars_link"
    fi
  elif [[ -e "$public_storage" ]]; then
    echo "Public storage boundary has an unsupported filesystem type."
    return 1
  elif [[ "$selective_required" == true ]]; then
    echo "Selective-media release is missing its tracked public storage boundary."
    return 1
  else
    ln -s "$shared_public_storage" "$public_storage"
  fi

  if [[ -d "$public_storage" && ! -L "$public_storage" ]] \
    && [[ ! -L "$avatars_link" \
      || "$(readlink -f "$avatars_link" 2>/dev/null || true)" != "$shared_avatars" ]]; then
    echo "Selective avatar storage link verification failed."
    return 1
  fi

  if sudo -u www-data test -w "$public_root" \
    || { [[ ! -L "$public_storage" ]] && sudo -u www-data test -w "$public_storage"; }; then
    echo "Application user can mutate the immutable public storage boundary."
    return 1
  fi
  if [[ -e "$marker" ]] && sudo -u www-data test -w "$marker"; then
    echo "Application user can mutate the immutable public storage boundary."
    return 1
  fi
}

if [[ "${1:-}" == "--verify-controls" ]]; then
  verify_deployment_controls "${2:-}" "${3:-}"
  exit 0
fi

EXPECTED_ROLLBACK_SHA="${1:-}"
EXPECTED_CURRENT_SHA="${2:-}"
EXPECTED_CONTROLS_SHA256="${3:-}"
DATA_DIR="/app/data"
RELEASES_DIR="${DATA_DIR}/releases"
CONTROL_RELEASES_DIR="/run/myapes-deployment-controls"
CONTROL_ROOT="${4:-}"
SHARED_DIR="${DATA_DIR}/shared"
CURRENT_LINK="${DATA_DIR}/current"
PREVIOUS_LINK="${DATA_DIR}/previous"
PHP_BIN="/usr/bin/php8.4"

if [[ ! "$EXPECTED_ROLLBACK_SHA" =~ ^[0-9a-f]{40}$ \
    || ! "$EXPECTED_CURRENT_SHA" =~ ^[0-9a-f]{40}$ \
    || ! "$EXPECTED_CONTROLS_SHA256" =~ ^[0-9a-f]{64}$ \
    || "$CONTROL_ROOT" != "${CONTROL_RELEASES_DIR}/${EXPECTED_CURRENT_SHA}" ]]; then
  echo "Rollback requires exact release identities and deployment-control manifest SHA-256."
  exit 1
fi

assert_rollback_path_boundaries

if [[ ! -L "$PREVIOUS_LINK" ]]; then
  echo "No previous release is available for code rollback."
  exit 1
fi

ROLLBACK_TARGET="$(readlink -f "$PREVIOUS_LINK")"
assert_canonical_deployment_directory \
  "${RELEASES_DIR}/${EXPECTED_ROLLBACK_SHA}" "rollback release root" true
ROLLBACK_SHA="$(basename "$ROLLBACK_TARGET")"
if [[ "$ROLLBACK_TARGET" != "${RELEASES_DIR}/${EXPECTED_ROLLBACK_SHA}" \
  || ! "$ROLLBACK_SHA" =~ ^[0-9a-f]{40}$ ]]; then
  echo "Previous release target is invalid: $ROLLBACK_TARGET"
  exit 1
fi

if [[ "$ROLLBACK_SHA" != "$EXPECTED_ROLLBACK_SHA" ]]; then
  echo "Previous release does not match the captured rollback identity."
  exit 1
fi
assert_release_runtime_path_boundaries "$ROLLBACK_TARGET" false

if [[ ! -L "$CURRENT_LINK" ]]; then
  echo "Current release symlink is unavailable; refusing code rollback."
  exit 1
fi

CURRENT_TARGET="$(readlink -f "$CURRENT_LINK")"
assert_canonical_deployment_directory \
  "${RELEASES_DIR}/${EXPECTED_CURRENT_SHA}" "current release root" true
CURRENT_SHA="$(basename "$CURRENT_TARGET")"
if [[ "$CURRENT_TARGET" != "${RELEASES_DIR}/${EXPECTED_CURRENT_SHA}" \
  || "$CURRENT_SHA" != "$EXPECTED_CURRENT_SHA" ]]; then
  echo "Current release does not match the failed activation identity."
  exit 1
fi
assert_release_runtime_path_boundaries "$CURRENT_TARGET" false

if [[ "$(readlink -f "${BASH_SOURCE[0]}")" \
  != "${CONTROL_ROOT}/scripts/deploy/rollback-release.sh" ]]; then
  echo "Rollback must execute the root-owned authenticated control copy."
  exit 1
fi

verify_deployment_controls "$CONTROL_ROOT" "$EXPECTED_CONTROLS_SHA256"
assert_hardened_control_directory "$CONTROL_ROOT"

for hardened_runtime_path in \
  scripts/deploy/cloudron-app.conf \
  scripts/deploy/cloudron-run.sh \
  DEPLOYMENT-CONTROLS.sha256; do
  if [[ ! -f "${CONTROL_ROOT}/${hardened_runtime_path}" ]]; then
    echo "Failed release is missing hardened runtime path: $hardened_runtime_path"
    exit 1
  fi
done

if [[ "$(tr -d '\r\n' <"${CURRENT_TARGET}/REVISION")" != "$CURRENT_SHA" ]]; then
  echo "Failed release REVISION does not match its immutable release path."
  exit 1
fi

if ! grep -Eq '^[[:space:]]*SetEnv[[:space:]]+APP_ENV[[:space:]]+production[[:space:]]*$' \
  "${CONTROL_ROOT}/scripts/deploy/cloudron-app.conf"; then
  echo "Failed release Apache configuration does not pin APP_ENV to production."
  exit 1
fi

if ! grep -Fqx 'export APP_ENV=production' \
  "${CONTROL_ROOT}/scripts/deploy/cloudron-run.sh" \
  || ! grep -Fq 'env APP_ENV=production' \
    "${CONTROL_ROOT}/scripts/deploy/cloudron-run.sh"; then
  echo "Failed release launcher does not pin APP_ENV to production."
  exit 1
fi

for required_path in \
  VERSION \
  REVISION \
  artisan \
  scripts/deploy/cloudron-app.conf \
  scripts/deploy/cloudron-run.sh; do
  if [[ ! -f "${ROLLBACK_TARGET}/${required_path}" ]]; then
    echo "Previous release is missing required path: $required_path"
    exit 1
  fi
done

if [[ "$(tr -d '\r\n' <"${ROLLBACK_TARGET}/REVISION")" != "$ROLLBACK_SHA" ]]; then
  echo "Previous release REVISION does not match its immutable release path."
  exit 1
fi

assert_release_root_ownership "$ROLLBACK_TARGET"

if [[ "$(stat -Lc '%U:%G' "$DATA_DIR")" != "root:root" \
  || "$(stat -Lc '%U:%G' "$RELEASES_DIR")" != "root:root" \
  || "$(stat -Lc '%U:%G' "$SHARED_DIR")" != "root:root" \
  || "$(stat -Lc '%U:%G' "${SHARED_DIR}/.env")" != "root:www-data" ]] \
  || sudo -u www-data test -w "${SHARED_DIR}/.env"; then
  echo "Shared rollback ownership is not root-controlled."
  exit 1
fi

for protected_path in "$ROLLBACK_TARGET" "${ROLLBACK_TARGET}/public"; do
  if sudo -u www-data test -w "$protected_path"; then
    echo "Previous release lost its immutable write boundary; refusing unauthenticated rollback."
    exit 1
  fi
done

if [[ -e "$CURRENT_LINK" && ! -L "$CURRENT_LINK" ]]; then
  echo "Current release path is not a symlink; refusing code rollback."
  exit 1
fi

maintenance_state_for_release() {
  local release_root="$1"
  local state=""

  if ! state="$(sudo -E -u www-data env APP_ENV=production \
      "$PHP_BIN" -r '
$root = $argv[1] ?? "";
require $root."/vendor/autoload.php";
$app = require $root."/bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
fwrite(STDOUT, $app->maintenanceMode()->active() ? "active" : "inactive");
' "$release_root")"; then
    echo "Unable to determine Laravel maintenance state." >&2
    return 1
  fi

  case "$state" in
    active|inactive)
      printf '%s\n' "$state"
      ;;
    *)
      echo "Laravel maintenance state returned an invalid result." >&2
      return 1
      ;;
  esac
}

MAINTENANCE_ACTIVE=false
PREEXISTING_MAINTENANCE=false
ROLLBACK_MAINTENANCE_ACTIVE=false
ROLLBACK_SWITCHED=false

restore_current_release() {
  local exit_code=$?
  local authorization_restored=false

  trap - EXIT

  if [[ "$exit_code" -eq 0 \
    || "$MAINTENANCE_ACTIVE" != true \
    || "$ROLLBACK_SWITCHED" == true ]]; then
    exit "$exit_code"
  fi

  set +e
  if sudo -E -u www-data env APP_ENV=production \
      "$PHP_BIN" "${CURRENT_TARGET}/artisan" permission:cache-reset --no-interaction --no-ansi \
    && sudo -E -u www-data env APP_ENV=production \
      "$PHP_BIN" "${CURRENT_TARGET}/artisan" myapes:authorization-sync --no-interaction --no-ansi \
    && sudo -E -u www-data env APP_ENV=production \
      "$PHP_BIN" "${CURRENT_TARGET}/artisan" permission:cache-reset --no-interaction --no-ansi \
    && sudo -E -u www-data env APP_ENV=production \
      "$PHP_BIN" "${CURRENT_TARGET}/artisan" myapes:authorization-check --no-interaction --no-ansi; then
    authorization_restored=true
  fi

  if [[ "$authorization_restored" == true && "$ROLLBACK_MAINTENANCE_ACTIVE" == true ]]; then
    sudo -E -u www-data env APP_ENV=production \
      "$PHP_BIN" "${CURRENT_TARGET}/artisan" up --no-interaction --no-ansi
  elif [[ "$authorization_restored" != true ]]; then
    echo "Current authorization could not be restored; maintenance mode remains active."
  fi

  exit "$exit_code"
}

trap restore_current_release EXIT

CURRENT_MAINTENANCE_STATE="$(maintenance_state_for_release "$CURRENT_TARGET")"
if [[ "$CURRENT_MAINTENANCE_STATE" == active ]]; then
  PREEXISTING_MAINTENANCE=true
else
  sudo -E -u www-data env APP_ENV=production \
    "$PHP_BIN" "${CURRENT_TARGET}/artisan" down --retry=60 --no-interaction --no-ansi
  ROLLBACK_MAINTENANCE_ACTIVE=true
fi
MAINTENANCE_ACTIVE=true

sudo -E -u www-data env APP_ENV=production \
  "$PHP_BIN" "${CURRENT_TARGET}/artisan" myapes:modules:rollback-check \
  --target-release="$ROLLBACK_TARGET" --no-interaction --no-ansi

for runtime_link in storage .env; do
  target_path="${ROLLBACK_TARGET}/${runtime_link}"
  shared_path="${SHARED_DIR}/${runtime_link}"

  if [[ -e "$target_path" && ! -L "$target_path" ]]; then
    echo "Previous release runtime path is not a symlink: $target_path"
    exit 1
  fi

  rm -f -- "$target_path"
  ln -s "$shared_path" "$target_path"
done

install_public_storage_boundary "$ROLLBACK_TARGET"

assert_release_runtime_path_boundaries "$ROLLBACK_TARGET" false
chown -hR root:root "$ROLLBACK_TARGET"
chmod -R a-w -- "$ROLLBACK_TARGET"
install -d -o www-data -g www-data -m 0770 "${ROLLBACK_TARGET}/bootstrap/cache"
assert_release_runtime_path_boundaries "$ROLLBACK_TARGET" true
chown -hR www-data:www-data "${ROLLBACK_TARGET}/bootstrap/cache"
chmod -R u+rwX,g+rwX,o-rwx "${ROLLBACK_TARGET}/bootstrap/cache"
chmod 0555 "$DATA_DIR" "$RELEASES_DIR" "$ROLLBACK_TARGET" "${ROLLBACK_TARGET}/public"

assert_canonical_deployment_directory "${DATA_DIR}/apache" "Apache runtime parent" true
assert_canonical_deployment_file \
  "${DATA_DIR}/apache/app.conf" "Apache runtime configuration" true
assert_canonical_deployment_file \
  "${DATA_DIR}/apache/app.conf.rollback" "staged Apache runtime configuration"
assert_canonical_deployment_file "${DATA_DIR}/run.sh" "Cloudron runtime launcher" true
assert_canonical_deployment_file \
  "${DATA_DIR}/run.sh.rollback" "staged Cloudron runtime launcher"
install -m 0444 "${CONTROL_ROOT}/scripts/deploy/cloudron-app.conf" "${DATA_DIR}/apache/app.conf.rollback"
install -m 0555 "${CONTROL_ROOT}/scripts/deploy/cloudron-run.sh" "${DATA_DIR}/run.sh.rollback"
assert_canonical_deployment_file \
  "${DATA_DIR}/apache/app.conf.rollback" "staged Apache runtime configuration" true
assert_canonical_deployment_file \
  "${DATA_DIR}/run.sh.rollback" "staged Cloudron runtime launcher" true

sudo -E -u www-data env APP_ENV=production \
  "$PHP_BIN" "${ROLLBACK_TARGET}/artisan" permission:cache-reset --no-interaction --no-ansi
sudo -E -u www-data env APP_ENV=production \
  "$PHP_BIN" "${ROLLBACK_TARGET}/artisan" myapes:authorization-sync --no-interaction --no-ansi
sudo -E -u www-data env APP_ENV=production \
  "$PHP_BIN" "${ROLLBACK_TARGET}/artisan" permission:cache-reset --no-interaction --no-ansi
sudo -E -u www-data env APP_ENV=production \
  "$PHP_BIN" "${ROLLBACK_TARGET}/artisan" myapes:authorization-check --no-interaction --no-ansi

rm -f -- "${CURRENT_LINK}.rollback"
ln -s "$ROLLBACK_TARGET" "${CURRENT_LINK}.rollback"
mv -Tf "${CURRENT_LINK}.rollback" "$CURRENT_LINK"
ROLLBACK_SWITCHED=true
assert_canonical_deployment_directory "${DATA_DIR}/apache" "Apache runtime parent" true
assert_canonical_deployment_file \
  "${DATA_DIR}/apache/app.conf" "Apache runtime configuration" true
assert_canonical_deployment_file \
  "${DATA_DIR}/apache/app.conf.rollback" "staged Apache runtime configuration" true
assert_canonical_deployment_file "${DATA_DIR}/run.sh" "Cloudron runtime launcher" true
assert_canonical_deployment_file \
  "${DATA_DIR}/run.sh.rollback" "staged Cloudron runtime launcher" true
mv -Tf "${DATA_DIR}/apache/app.conf.rollback" "${DATA_DIR}/apache/app.conf"
mv -Tf "${DATA_DIR}/run.sh.rollback" "${DATA_DIR}/run.sh"
rm -f -- "$PREVIOUS_LINK"
chown root:root "$DATA_DIR" "${DATA_DIR}/apache" "$RELEASES_DIR" "$SHARED_DIR" \
  "${DATA_DIR}/apache/app.conf" "${DATA_DIR}/run.sh"
chown -h root:root "$CURRENT_LINK"
assert_canonical_deployment_file \
  "${SHARED_DIR}/.env" "shared environment" true
chown root:www-data "${SHARED_DIR}/.env"
chmod 0640 "${SHARED_DIR}/.env"
chmod 0555 "$DATA_DIR" "${DATA_DIR}/apache" "$RELEASES_DIR" "$SHARED_DIR"

assert_release_root_ownership "$ROLLBACK_TARGET"

for protected_path in \
  "$DATA_DIR" \
  "${DATA_DIR}/apache" \
  "${DATA_DIR}/apache/app.conf" \
  "${DATA_DIR}/run.sh" \
  "$RELEASES_DIR" \
  "$ROLLBACK_TARGET" \
  "${ROLLBACK_TARGET}/public"; do
  if [[ "$(stat -Lc '%U:%G' "$protected_path")" != "root:root" ]] \
    || sudo -u www-data test -w "$protected_path"; then
    echo "Application user can mutate restored release control: $protected_path"
    exit 1
  fi
done

if [[ "$ROLLBACK_MAINTENANCE_ACTIVE" == true ]]; then
  sudo -E -u www-data env APP_ENV=production \
    "$PHP_BIN" "${ROLLBACK_TARGET}/artisan" up --no-interaction --no-ansi
fi
MAINTENANCE_ACTIVE=false
trap - EXIT

echo "Rolled back code to ${ROLLBACK_SHA}. Database migrations were retained and were not reversed."
