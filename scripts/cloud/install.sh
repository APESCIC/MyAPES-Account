#!/usr/bin/env bash
set -euo pipefail

# Cloud Agent install: provision PHP/Composer on the base image, then run
# the same local bootstrap used on developer machines. Idempotent.
#
# scripts/local/bootstrap.sh requires php, composer, and npm to already exist.
# The default Cloud Agent image has Node/npm only, which is why builds that
# call bootstrap.sh directly fail with "Missing required tool: php".

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT_DIR"

export DEBIAN_FRONTEND=noninteractive

php_meets_requirement() {
  command -v php >/dev/null 2>&1 || return 1
  php -r 'exit(PHP_VERSION_ID >= 80300 ? 0 : 1);'
}

install_php() {
  if php_meets_requirement; then
    echo "PHP $(php -r 'echo PHP_VERSION;') already installed."
    return 0
  fi

  echo "Installing PHP 8.3+ and Laravel extensions..."
  sudo apt-get update -y

  sudo apt-get install -y --no-install-recommends \
    ca-certificates \
    curl \
    gnupg \
    software-properties-common \
    unzip \
    zip

  if sudo add-apt-repository -y ppa:ondrej/php \
    && sudo apt-get update -y \
    && sudo apt-get install -y --no-install-recommends \
      php8.4-cli \
      php8.4-bcmath \
      php8.4-curl \
      php8.4-gd \
      php8.4-intl \
      php8.4-mbstring \
      php8.4-readline \
      php8.4-sqlite3 \
      php8.4-xml \
      php8.4-zip; then
    echo "Installed PHP 8.4 from ondrej/php."
  else
    echo "ondrej/php PPA unavailable; falling back to Ubuntu php8.3 packages."
    sudo apt-get install -y --no-install-recommends \
      php8.3-cli \
      php8.3-bcmath \
      php8.3-curl \
      php8.3-gd \
      php8.3-intl \
      php8.3-mbstring \
      php8.3-readline \
      php8.3-sqlite3 \
      php8.3-xml \
      php8.3-zip
  fi

  if ! php_meets_requirement; then
    echo "PHP 8.3+ is still missing after package install."
    exit 1
  fi

  echo "Installed PHP $(php -r 'echo PHP_VERSION;')"
}

install_composer() {
  if command -v composer >/dev/null 2>&1; then
    echo "Composer $(composer --version --no-ansi | head -n 1) already installed."
    return 0
  fi

  echo "Installing Composer..."
  local installer
  installer="$(mktemp)"
  curl -fsSL https://getcomposer.org/installer -o "$installer"
  sudo php "$installer" --install-dir=/usr/local/bin --filename=composer
  rm -f "$installer"

  if ! command -v composer >/dev/null 2>&1; then
    echo "Composer is still missing after install."
    exit 1
  fi
}

if ! command -v npm >/dev/null 2>&1; then
  echo "Missing required tool: npm"
  echo "The Cloud Agent image is expected to provide Node.js and npm."
  exit 1
fi

install_php
install_composer

exec bash scripts/local/bootstrap.sh --fresh
