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

if [[ "${1:-}" == "--verify-controls" ]]; then
  verify_deployment_controls "${2:-}" "${3:-}"
  exit 0
fi

EXPECTED_ROLLBACK_SHA="${1:-}"
EXPECTED_CURRENT_SHA="${2:-}"
EXPECTED_CONTROLS_SHA256="${3:-}"
DATA_DIR="/app/data"
RELEASES_DIR="${DATA_DIR}/releases"
CONTROL_RELEASES_DIR="${DATA_DIR}/deployment-controls"
CONTROL_ROOT="${CONTROL_RELEASES_DIR}/${EXPECTED_CURRENT_SHA}"
SHARED_DIR="${DATA_DIR}/shared"
CURRENT_LINK="${DATA_DIR}/current"
PREVIOUS_LINK="${DATA_DIR}/previous"

if [[ ! -L "$PREVIOUS_LINK" ]]; then
  echo "No previous release is available for code rollback."
  exit 1
fi

if [[ ! "$EXPECTED_ROLLBACK_SHA" =~ ^[0-9a-f]{40}$ \
  || ! "$EXPECTED_CURRENT_SHA" =~ ^[0-9a-f]{40}$ \
  || ! "$EXPECTED_CONTROLS_SHA256" =~ ^[0-9a-f]{64}$ ]]; then
  echo "Rollback requires exact release identities and deployment-control manifest SHA-256."
  exit 1
fi

ROLLBACK_TARGET="$(readlink -f "$PREVIOUS_LINK")"
ROLLBACK_SHA="$(basename "$ROLLBACK_TARGET")"
if [[ "$ROLLBACK_TARGET" != "${RELEASES_DIR}/"* || ! "$ROLLBACK_SHA" =~ ^[0-9a-f]{40}$ ]]; then
  echo "Previous release target is invalid: $ROLLBACK_TARGET"
  exit 1
fi

if [[ "$ROLLBACK_SHA" != "$EXPECTED_ROLLBACK_SHA" ]]; then
  echo "Previous release does not match the captured rollback identity."
  exit 1
fi

if [[ ! -L "$CURRENT_LINK" ]]; then
  echo "Current release symlink is unavailable; refusing code rollback."
  exit 1
fi

CURRENT_TARGET="$(readlink -f "$CURRENT_LINK")"
CURRENT_SHA="$(basename "$CURRENT_TARGET")"
if [[ "$CURRENT_TARGET" != "${RELEASES_DIR}/"* \
  || "$CURRENT_SHA" != "$EXPECTED_CURRENT_SHA" ]]; then
  echo "Current release does not match the failed activation identity."
  exit 1
fi

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

if [[ -e "$CURRENT_LINK" && ! -L "$CURRENT_LINK" ]]; then
  echo "Current release path is not a symlink; refusing code rollback."
  exit 1
fi

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

install -m 0644 "${CONTROL_ROOT}/scripts/deploy/cloudron-app.conf" "${DATA_DIR}/apache/app.conf.rollback"
install -m 0755 "${CONTROL_ROOT}/scripts/deploy/cloudron-run.sh" "${DATA_DIR}/run.sh.rollback"

rm -f -- "${CURRENT_LINK}.rollback"
ln -s "$ROLLBACK_TARGET" "${CURRENT_LINK}.rollback"
mv -Tf "${CURRENT_LINK}.rollback" "$CURRENT_LINK"
mv -Tf "${DATA_DIR}/apache/app.conf.rollback" "${DATA_DIR}/apache/app.conf"
mv -Tf "${DATA_DIR}/run.sh.rollback" "${DATA_DIR}/run.sh"
rm -f -- "$PREVIOUS_LINK"

echo "Rolled back code to ${ROLLBACK_SHA}. Database migrations were retained and were not reversed."
