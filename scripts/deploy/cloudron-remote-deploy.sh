#!/usr/bin/env bash
set -euo pipefail

required_env_vars=(CLOUDRON_APP_ID CLOUDRON_DEPLOY_REF)
for env_var in "${required_env_vars[@]}"; do
  if [[ -z "${!env_var:-}" ]]; then
    echo "Missing required environment variable: $env_var"
    exit 1
  fi
done

CLOUDRON_APP_CODE_PATH="${CLOUDRON_APP_CODE_PATH:-/app/code}"
CLOUDRON_RESTART_STRATEGY="${CLOUDRON_RESTART_STRATEGY:-auto}"

if [[ -z "${CLOUDRON_APP_CONTAINER:-}" ]]; then
  CLOUDRON_APP_CONTAINER="$(docker ps --filter "label=appId=${CLOUDRON_APP_ID}" --format '{{.ID}}' | head -n 1)"
fi

if [[ -z "${CLOUDRON_APP_CONTAINER}" ]]; then
  echo "Could not resolve Cloudron app container for app ID ${CLOUDRON_APP_ID}."
  exit 1
fi

echo "Deploying ref '${CLOUDRON_DEPLOY_REF}' to container '${CLOUDRON_APP_CONTAINER}'..."

docker exec "${CLOUDRON_APP_CONTAINER}" sh -lc "
  set -eu
  cd '${CLOUDRON_APP_CODE_PATH}'
  test -d .git
  git fetch --all --prune
  git fetch --tags
  if git show-ref --verify --quiet 'refs/remotes/origin/${CLOUDRON_DEPLOY_REF}'; then
    git checkout --force -B '${CLOUDRON_DEPLOY_REF}' 'origin/${CLOUDRON_DEPLOY_REF}'
  elif git show-ref --verify --quiet 'refs/tags/${CLOUDRON_DEPLOY_REF}'; then
    git checkout --force 'tags/${CLOUDRON_DEPLOY_REF}'
  else
    echo 'Ref not found as origin branch or tag: ${CLOUDRON_DEPLOY_REF}'
    exit 1
  fi
  composer install --no-dev --optimize-autoloader --no-interaction
  php artisan migrate --force
  php artisan optimize:clear
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
  php artisan storage:link --force
"

if [[ "${CLOUDRON_RESTART_STRATEGY}" == "cloudron-cli" ]] || { [[ "${CLOUDRON_RESTART_STRATEGY}" == "auto" ]] && command -v cloudron >/dev/null 2>&1; }; then
  cloudron restart --app "${CLOUDRON_APP_ID}"
elif [[ "${CLOUDRON_RESTART_STRATEGY}" == "docker" ]] || [[ "${CLOUDRON_RESTART_STRATEGY}" == "auto" ]]; then
  docker restart "${CLOUDRON_APP_CONTAINER}" >/dev/null
else
  echo "Unsupported CLOUDRON_RESTART_STRATEGY: ${CLOUDRON_RESTART_STRATEGY}"
  exit 1
fi

if [[ -z "$(docker ps --filter "id=${CLOUDRON_APP_CONTAINER}" --filter "status=running" --quiet)" ]]; then
  echo "Container ${CLOUDRON_APP_CONTAINER} is not running after restart."
  exit 1
fi

echo "Cloudron deployment completed."
