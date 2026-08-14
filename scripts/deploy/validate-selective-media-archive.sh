#!/usr/bin/env bash

set -euo pipefail

MARKER_NAME=".myapes-selective-media"
MARKER_CONTENT="myapes-selective-media:v1"

validate_source_tree() {
  local release_root="${1:-}"
  local public_storage=""
  local marker=""
  local unexpected_entry=""
  local unexpected_link=""
  local protected_directory=""
  local canonical_directory=""

  if [[ -z "$release_root" || -L "$release_root" || ! -d "$release_root" ]]; then
    echo "Release source requires an ordinary release directory." >&2
    return 1
  fi
  if [[ "$release_root" == . ]]; then
    release_root="$(pwd -P)"
  elif [[ "$release_root" != /* ]]; then
    if [[ "$release_root" == *//* \
      || "$release_root" =~ (^|/)\.{1,2}(/|$) \
      || "$release_root" == *\\* ]]; then
      echo "Release source path is non-canonical." >&2
      return 1
    fi
    release_root="$(pwd -P)/${release_root#./}"
  fi
  public_storage="${release_root}/public/storage"
  marker="${public_storage}/${MARKER_NAME}"
  if [[ ! -d "$public_storage" || -L "$public_storage" ]]; then
    echo "Release source requires a real selective public storage directory." >&2
    return 1
  fi
  unexpected_link="$(find "$release_root" -type l -print -quit)"
  if [[ -n "$unexpected_link" ]]; then
    echo "Release source contains an unsupported symbolic link: $unexpected_link" >&2
    return 1
  fi
  for protected_directory in \
    "$release_root" \
    "${release_root}/public" \
    "${release_root}/bootstrap" \
    "${release_root}/bootstrap/cache"; do
    if [[ ! -d "$protected_directory" || -L "$protected_directory" ]]; then
      echo "Release source contains an unsafe runtime directory: $protected_directory" >&2
      return 1
    fi
    canonical_directory="$(readlink -f -- "$protected_directory" 2>/dev/null || true)"
    if [[ -z "$canonical_directory" || "$canonical_directory" != "$protected_directory" ]]; then
      echo "Release source contains a non-canonical runtime directory: $protected_directory" >&2
      return 1
    fi
  done
  if [[ ! -f "$marker" || -L "$marker" ]] \
    || ! cmp -s "$marker" <(printf '%s\n' "$MARKER_CONTENT"); then
    echo "Release source marker is missing, unsafe, or changed." >&2
    return 1
  fi
  unexpected_entry="$(find "$public_storage" -mindepth 1 -maxdepth 1 \
    ! -name "$MARKER_NAME" -print -quit)"
  if [[ -n "$unexpected_entry" ]]; then
    echo "Release source contains an unexpected public storage entry: $unexpected_entry" >&2
    return 1
  fi
}

validate_archive() {
  local archive="${1:-}"
  local archive_list=""
  local normalized_archive_list=""
  local storage_members=""
  local canonical_parent_count=""
  local normalized_parent_count=""
  local parent_listing=""
  local marker_listing=""
  local unexpected_entry=""
  local duplicate_member=""
  local unsafe_type_listing=""
  local member=""
  local relative_member=""
  local component_path=""
  local collision_key=""
  local required_directory=""
  local required_directory_listing=""
  local -A collision_keys=()

  if [[ -z "$archive" || ! -f "$archive" || -L "$archive" ]]; then
    echo "Release archive is missing or unsafe." >&2
    return 1
  fi
  if ! archive_list="$(tar --absolute-names --quoting-style=escape -tzf "$archive")"; then
    echo "Release archive member listing failed." >&2
    return 1
  fi
  while IFS= read -r member || [[ -n "$member" ]]; do
    if [[ -z "$member" \
      || "$member" == /* \
      || "$member" != ./* \
      || "$member" == *\\* ]]; then
      echo "Release archive contains an unsafe or non-canonical member name." >&2
      return 1
    fi
    relative_member="${member#./}"
    [[ -n "$relative_member" ]] || continue
    component_path="${relative_member%/}"
    if [[ -z "$component_path" \
      || "$component_path" == *//* \
      || "$component_path" =~ (^|/)\.{1,2}(/|$) ]]; then
      echo "Release archive contains an aliased member path." >&2
      return 1
    fi
    collision_key="${relative_member%/}"
    if [[ -n "${collision_keys[$collision_key]+present}" ]]; then
      echo "Release archive contains colliding member paths: $collision_key" >&2
      return 1
    fi
    collision_keys["$collision_key"]=1
  done <<<"$archive_list"
  duplicate_member="$(printf '%s\n' "$archive_list" \
    | LC_ALL=C sort | uniq -d | head -n 1 || true)"
  if [[ -n "$duplicate_member" ]]; then
    echo "Release archive contains a duplicate member: $duplicate_member" >&2
    return 1
  fi
  unsafe_type_listing="$(tar --absolute-names --quoting-style=escape -tvzf "$archive" \
    | awk 'substr($0, 1, 1) != "d" && substr($0, 1, 1) != "-" { print; exit }')"
  if [[ -n "$unsafe_type_listing" ]]; then
    echo "Release archive contains an unsupported filesystem entry type." >&2
    return 1
  fi
  for required_directory in './bootstrap/' './bootstrap/cache/'; do
    if [[ "$(printf '%s\n' "$archive_list" | grep -Fxc "$required_directory" || true)" != 1 ]]; then
      echo "Release archive is missing one canonical runtime directory: $required_directory" >&2
      return 1
    fi
    required_directory_listing="$(tar -tvzf "$archive" --no-recursion "$required_directory")"
    if [[ "${required_directory_listing:0:1}" != d ]]; then
      echo "Release archive runtime parent is not a directory: $required_directory" >&2
      return 1
    fi
  done

  normalized_archive_list="$(printf '%s\n' "$archive_list" | sed 's#^\./##')"
  storage_members="$(printf '%s\n' "$normalized_archive_list" \
    | grep -E '^public/storage($|/)' || true)"
  canonical_parent_count="$(printf '%s\n' "$archive_list" \
    | grep -Fxc './public/storage/' || true)"
  normalized_parent_count="$(printf '%s\n' "$storage_members" \
    | grep -Fxc 'public/storage/' || true)"
  if [[ "$canonical_parent_count" != 1 || "$normalized_parent_count" != 1 ]]; then
    echo "Release archive does not contain exactly one canonical public storage directory." >&2
    return 1
  fi
  unexpected_entry="$(printf '%s\n' "$storage_members" \
    | grep -Ev '^public/storage/$|^public/storage/\.myapes-selective-media$' \
    | head -n 1 || true)"
  if [[ -n "$unexpected_entry" ]]; then
    echo "Release archive contains an unexpected public storage member: $unexpected_entry" >&2
    return 1
  fi
  parent_listing="$(tar -tvzf "$archive" --no-recursion './public/storage/')"
  if [[ "${parent_listing:0:1}" != d ]]; then
    echo "Release archive public storage parent is not a directory." >&2
    return 1
  fi
  if [[ "$(printf '%s\n' "$normalized_archive_list" \
    | grep -cx 'public/storage/\.myapes-selective-media')" != 1 ]]; then
    echo "Release archive does not contain exactly one selective-media marker." >&2
    return 1
  fi
  marker_listing="$(tar -tvzf "$archive" './public/storage/.myapes-selective-media')"
  if [[ "${marker_listing:0:1}" != - ]]; then
    echo "Release archive marker is not a regular file." >&2
    return 1
  fi
  if ! tar -xOzf "$archive" './public/storage/.myapes-selective-media' \
    | cmp -s - <(printf '%s\n' "$MARKER_CONTENT"); then
    echo "Release archive marker content is changed." >&2
    return 1
  fi
}

case "${1:-}" in
  source)
    validate_source_tree "${2:-}"
    ;;
  archive)
    validate_archive "${2:-}"
    ;;
  *)
    echo "Usage: $0 <source|archive> <path>" >&2
    exit 2
    ;;
esac
