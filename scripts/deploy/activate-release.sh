#!/usr/bin/env bash
set -euo pipefail

RELEASE_SHA="${1:-}"
DATA_DIR="/app/data"
DEPLOY_DIR="${DATA_DIR}/.deploy/${RELEASE_SHA}"
ARCHIVE_PATH="${DEPLOY_DIR}/release.tar.gz"
RELEASES_DIR="${DATA_DIR}/releases"
RELEASE_DIR="${RELEASES_DIR}/${RELEASE_SHA}"
TEMP_RELEASE_DIR="${RELEASE_DIR}.extracting"
SHARED_DIR="${DATA_DIR}/shared"
CURRENT_LINK="${DATA_DIR}/current"
PREVIOUS_LINK="${DATA_DIR}/previous"
PHP_BIN="/usr/bin/php8.4"

if [[ ! "$RELEASE_SHA" =~ ^[0-9a-f]{40}$ ]]; then
  echo "Release SHA must be a full 40-character Git commit SHA."
  exit 1
fi

if [[ ! -f "$ARCHIVE_PATH" ]]; then
  echo "Release archive is missing: $ARCHIVE_PATH"
  exit 1
fi

for release_link in "$CURRENT_LINK" "$PREVIOUS_LINK"; do
  if [[ -e "$release_link" && ! -L "$release_link" ]]; then
    echo "Refusing activation because $release_link exists and is not a symlink."
    exit 1
  fi
done

CURRENT_TARGET_BEFORE="$(readlink -f "$CURRENT_LINK" 2>/dev/null || true)"
PREVIOUS_TARGET_BEFORE="$(readlink -f "$PREVIOUS_LINK" 2>/dev/null || true)"

if [[ -L "$CURRENT_LINK" && -z "$CURRENT_TARGET_BEFORE" ]]; then
  echo "Current release symlink is dangling; refusing activation."
  exit 1
fi

if [[ -L "$PREVIOUS_LINK" && -z "$PREVIOUS_TARGET_BEFORE" ]]; then
  echo "Previous release symlink is dangling; refusing activation."
  exit 1
fi

for release_target in "$CURRENT_TARGET_BEFORE" "$PREVIOUS_TARGET_BEFORE"; do
  [[ -n "$release_target" ]] || continue
  if [[ "$release_target" != "${RELEASES_DIR}/"* || ! -d "$release_target" ]]; then
    echo "Release symlink points outside ${RELEASES_DIR} or to a missing directory: $release_target"
    exit 1
  fi
done

install -d -m 0755 "$RELEASES_DIR"
install -d -o www-data -g www-data -m 0775 \
  "${SHARED_DIR}/storage/app/public" \
  "${SHARED_DIR}/storage/framework/cache/data" \
  "${SHARED_DIR}/storage/framework/sessions" \
  "${SHARED_DIR}/storage/framework/views" \
  "${SHARED_DIR}/storage/logs"

if [[ ! -f "${SHARED_DIR}/.env" ]]; then
  install -d -m 0755 "$SHARED_DIR"
  tar --extract --gzip --to-stdout --file "$ARCHIVE_PATH" \
    ./scripts/deploy/production.env.example >"${SHARED_DIR}/.env"
  APP_KEY_VALUE="$("$PHP_BIN" -r 'echo "base64:".base64_encode(random_bytes(32));')"
  sed -i "s|^APP_KEY=.*$|APP_KEY=${APP_KEY_VALUE}|" "${SHARED_DIR}/.env"
  chown www-data:www-data "${SHARED_DIR}/.env"
  chmod 0640 "${SHARED_DIR}/.env"
  echo "Created the shared production environment. Configure OIDC values before staff sign-in acceptance."
elif ! grep -Eq '^APP_ENV=production$' "${SHARED_DIR}/.env"; then
  echo "Refusing deployment because ${SHARED_DIR}/.env is not marked APP_ENV=production."
  exit 1
fi

required_paths=(
  artisan
  public/index.php
  vendor/autoload.php
  public/build/manifest.json
  scripts/deploy/cloudron-app.conf
  scripts/deploy/cloudron-run.sh
  scripts/deploy/production.env.example
  scripts/deploy/rollback-release.sh
  REVISION
)

reuse_existing_release=false
if [[ -d "$RELEASE_DIR" ]]; then
  packaged_revision="$(tr -d '\r\n' <"${RELEASE_DIR}/REVISION" 2>/dev/null || true)"
  existing_release_valid=true

  for required_path in "${required_paths[@]}"; do
    if [[ ! -e "${RELEASE_DIR}/${required_path}" ]]; then
      existing_release_valid=false
      break
    fi
  done

  if [[ "$existing_release_valid" == true && "$packaged_revision" == "$RELEASE_SHA" ]]; then
    reuse_existing_release=true
    echo "Reusing existing immutable release ${RELEASE_SHA}."
  elif [[ "$RELEASE_DIR" == "$CURRENT_TARGET_BEFORE" || "$RELEASE_DIR" == "$PREVIOUS_TARGET_BEFORE" ]]; then
    echo "Referenced release ${RELEASE_DIR} is incomplete; refusing to remove it."
    exit 1
  else
    case "$RELEASE_DIR" in
      "${RELEASES_DIR}/"*) rm -rf -- "$RELEASE_DIR" ;;
      *) echo "Unsafe release path: $RELEASE_DIR"; exit 1 ;;
    esac
  fi
fi

if [[ "$reuse_existing_release" == false ]]; then
  if [[ -e "$TEMP_RELEASE_DIR" ]]; then
    case "$TEMP_RELEASE_DIR" in
      "${RELEASES_DIR}/"*.extracting) rm -rf -- "$TEMP_RELEASE_DIR" ;;
      *) echo "Unsafe temporary release path: $TEMP_RELEASE_DIR"; exit 1 ;;
    esac
  fi

  install -d -m 0755 "$TEMP_RELEASE_DIR"
  tar --extract --gzip --file "$ARCHIVE_PATH" --directory "$TEMP_RELEASE_DIR" --no-same-owner

  for required_path in "${required_paths[@]}"; do
    if [[ ! -e "${TEMP_RELEASE_DIR}/${required_path}" ]]; then
      echo "Packaged release is missing required path: $required_path"
      exit 1
    fi
  done

  if [[ "$(tr -d '\r\n' <"${TEMP_RELEASE_DIR}/REVISION")" != "$RELEASE_SHA" ]]; then
    echo "Packaged REVISION does not match requested release SHA."
    exit 1
  fi

  rm -rf -- "${TEMP_RELEASE_DIR}/storage"
  ln -s "${SHARED_DIR}/storage" "${TEMP_RELEASE_DIR}/storage"
  ln -s "${SHARED_DIR}/.env" "${TEMP_RELEASE_DIR}/.env"
  install -d -o www-data -g www-data -m 0775 "${TEMP_RELEASE_DIR}/bootstrap/cache"
  chown -R www-data:www-data "$TEMP_RELEASE_DIR"
  mv "$TEMP_RELEASE_DIR" "$RELEASE_DIR"
fi

rm -rf -- "${RELEASE_DIR}/storage"
rm -f -- "${RELEASE_DIR}/.env"
ln -s "${SHARED_DIR}/storage" "${RELEASE_DIR}/storage"
ln -s "${SHARED_DIR}/.env" "${RELEASE_DIR}/.env"
install -d -o www-data -g www-data -m 0775 "${RELEASE_DIR}/bootstrap/cache"

sudo -E -u www-data "$PHP_BIN" "${RELEASE_DIR}/artisan" optimize:clear
sudo -E -u www-data "$PHP_BIN" "${RELEASE_DIR}/artisan" migrate --force
sudo -E -u www-data "$PHP_BIN" "${RELEASE_DIR}/artisan" storage:link --force
sudo -E -u www-data "$PHP_BIN" "${RELEASE_DIR}/artisan" config:cache
sudo -E -u www-data "$PHP_BIN" "${RELEASE_DIR}/artisan" route:cache
sudo -E -u www-data "$PHP_BIN" "${RELEASE_DIR}/artisan" view:cache

install -m 0644 "${RELEASE_DIR}/scripts/deploy/cloudron-app.conf" "${DATA_DIR}/apache/app.conf"
install -m 0755 "${RELEASE_DIR}/scripts/deploy/cloudron-run.sh" "${DATA_DIR}/run.sh"

rm -f -- "${CURRENT_LINK}.next" "${PREVIOUS_LINK}.next"
ln -s "$RELEASE_DIR" "${CURRENT_LINK}.next"

NEXT_PREVIOUS_TARGET=""
if [[ -n "$CURRENT_TARGET_BEFORE" && "$CURRENT_TARGET_BEFORE" != "$RELEASE_DIR" ]]; then
  NEXT_PREVIOUS_TARGET="$CURRENT_TARGET_BEFORE"
elif [[ -n "$PREVIOUS_TARGET_BEFORE" && "$PREVIOUS_TARGET_BEFORE" != "$RELEASE_DIR" ]]; then
  NEXT_PREVIOUS_TARGET="$PREVIOUS_TARGET_BEFORE"
fi

if [[ -n "$NEXT_PREVIOUS_TARGET" ]]; then
  ln -s "$NEXT_PREVIOUS_TARGET" "${PREVIOUS_LINK}.next"
  mv -Tf "${PREVIOUS_LINK}.next" "$PREVIOUS_LINK"
else
  rm -f -- "$PREVIOUS_LINK"
fi

# This is the commit point: all fallible preparation happens before the atomic switch.
mv -Tf "${CURRENT_LINK}.next" "$CURRENT_LINK"

CURRENT_TARGET="$(readlink -f "$CURRENT_LINK")"
PREVIOUS_TARGET="$(readlink -f "$PREVIOUS_LINK" 2>/dev/null || true)"
for candidate in "${RELEASES_DIR}"/*; do
  [[ -d "$candidate" ]] || continue
  CANDIDATE_TARGET="$(readlink -f "$candidate")"
  if [[ "$CANDIDATE_TARGET" != "$CURRENT_TARGET" && "$CANDIDATE_TARGET" != "$PREVIOUS_TARGET" ]]; then
    case "$CANDIDATE_TARGET" in
      "${RELEASES_DIR}/"*)
        if ! rm -rf -- "$CANDIDATE_TARGET"; then
          echo "Warning: unable to prune old release $CANDIDATE_TARGET."
        fi
        ;;
      *) echo "Skipping unsafe release cleanup target: $CANDIDATE_TARGET" ;;
    esac
  fi
done

if ! rm -rf -- "$DEPLOY_DIR"; then
  echo "Warning: unable to remove deployment staging directory $DEPLOY_DIR."
fi

echo "Activated MyAPES release ${RELEASE_SHA}."
