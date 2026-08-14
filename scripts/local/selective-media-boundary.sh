#!/usr/bin/env bash

assert_ordinary_canonical_directory() {
  local path="${1:-}"
  local description="${2:-Directory}"
  local canonical_path=""

  if [[ -z "$path" || ! -d "$path" || -L "$path" ]]; then
    echo "$description is missing or unsafe." >&2
    return 1
  fi
  canonical_path="$(readlink -f "$path" 2>/dev/null || true)"
  if [[ -z "$canonical_path" || "$canonical_path" != "$path" ]]; then
    echo "$description has a linked or non-canonical ancestor." >&2
    return 1
  fi
}

ensure_selective_media_boundary() {
  local root_dir="${1:-}"
  local create_avatar_link="${2:-false}"
  local public_root="${root_dir}/public"
  local public_storage="${root_dir}/public/storage"
  local marker="${public_storage}/.myapes-selective-media"
  local avatar_link="${public_storage}/avatars"
  local storage_root="${root_dir}/storage"
  local storage_app="${storage_root}/app"
  local storage_public="${storage_app}/public"
  local avatar_target="${storage_public}/avatars"
  local unexpected_entry=""

  assert_ordinary_canonical_directory "$root_dir" "Application root"
  assert_ordinary_canonical_directory "$public_root" "Public root"
  assert_ordinary_canonical_directory \
    "$public_storage" "Selective public storage boundary"
  if [[ ! -f "$marker" || -L "$marker" ]] \
    || ! cmp -s "$marker" <(printf '%s\n' 'myapes-selective-media:v1'); then
    echo "Selective-media marker is missing, unsafe, or unexpected." >&2
    return 1
  fi
  unexpected_entry="$(find "$public_storage" -mindepth 1 -maxdepth 1 \
    ! -name .myapes-selective-media ! -name avatars -print -quit)"
  if [[ -n "$unexpected_entry" ]]; then
    echo "Selective public storage contains an unexpected entry: $unexpected_entry" >&2
    return 1
  fi

  assert_ordinary_canonical_directory "$storage_root" "Shared storage root"
  assert_ordinary_canonical_directory "$storage_app" "Shared storage app directory"
  assert_ordinary_canonical_directory "$storage_public" "Shared public storage directory"
  if [[ -e "$avatar_target" || -L "$avatar_target" ]]; then
    assert_ordinary_canonical_directory "$avatar_target" "Shared avatars target"
  else
    mkdir -- "$avatar_target"
    assert_ordinary_canonical_directory "$avatar_target" "Shared avatars target"
  fi
  if [[ ! -e "$avatar_link" && ! -L "$avatar_link" ]]; then
    if [[ "$create_avatar_link" != true ]]; then
      echo "Avatar public storage link is missing." >&2
      return 1
    fi
    (cd "$root_dir" && php artisan storage:link)
  fi
  if [[ ! -L "$avatar_link" \
    || "$(readlink -f "$avatar_link" 2>/dev/null || true)" != "$avatar_target" ]]; then
    echo "Avatar public storage link is missing or targets an unexpected path." >&2
    return 1
  fi
}

if [[ "${BASH_SOURCE[0]}" == "$0" ]]; then
  set -euo pipefail
  ensure_selective_media_boundary "${1:-}" false
fi
