#!/usr/bin/env bash
set -euo pipefail

DATA_DIR="/app/data"
RELEASES_DIR="${DATA_DIR}/releases"
CURRENT_LINK="${DATA_DIR}/current"
PREVIOUS_LINK="${DATA_DIR}/previous"

if [[ ! -L "$PREVIOUS_LINK" ]]; then
  echo "No previous release is available for code rollback."
  exit 1
fi

ROLLBACK_TARGET="$(readlink -f "$PREVIOUS_LINK")"
if [[ "$ROLLBACK_TARGET" != "${RELEASES_DIR}/"* || ! -f "${ROLLBACK_TARGET}/artisan" ]]; then
  echo "Previous release target is invalid: $ROLLBACK_TARGET"
  exit 1
fi

if [[ -e "$CURRENT_LINK" && ! -L "$CURRENT_LINK" ]]; then
  echo "Current release path is not a symlink; refusing code rollback."
  exit 1
fi

rm -f -- "${CURRENT_LINK}.rollback"
ln -s "$ROLLBACK_TARGET" "${CURRENT_LINK}.rollback"
mv -Tf "${CURRENT_LINK}.rollback" "$CURRENT_LINK"
rm -f -- "$PREVIOUS_LINK"

install -m 0644 "${ROLLBACK_TARGET}/scripts/deploy/cloudron-app.conf" "${DATA_DIR}/apache/app.conf"
install -m 0755 "${ROLLBACK_TARGET}/scripts/deploy/cloudron-run.sh" "${DATA_DIR}/run.sh"

echo "Rolled back code to $(basename "$ROLLBACK_TARGET"). Database migrations were not reversed."
