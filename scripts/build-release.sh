#!/usr/bin/env bash
# Build a ready-to-upload release zip (application + vendor/, no dev files).   Usage: scripts/build-release.sh
set -euo pipefail

root="$(cd "$(dirname "$0")/.." && pwd)"
version="$(tr -d '[:space:]' < "$root/VERSION")"
out="$root/dist"; work="$(mktemp -d)"; name="nexcart-$version"
trap 'rm -rf "$work"' EXIT

git -C "$root" diff --quiet && git -C "$root" diff --cached --quiet || { echo "Commit or stash your changes first — the release is built from HEAD." >&2; exit 1; }

mkdir -p "$work/$name" "$out"
git -C "$root" archive HEAD | tar -x -C "$work/$name"        # honours .gitattributes export-ignore
( cd "$work/$name" && composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader )

# runtime folders the web server must be able to write to
mkdir -p "$work/$name"/storage/{app/public,app/private,app/backups,framework/cache,framework/sessions,framework/views,logs} "$work/$name/bootstrap/cache"
find "$work/$name/storage" "$work/$name/bootstrap/cache" -type d -exec touch {}/.gitkeep \; 2>/dev/null || true

( cd "$work" && zip -qr "$out/$name.zip" "$name" )
( cd "$out" && sha256sum "$name.zip" > "$name.zip.sha256" )
echo "Built $out/$name.zip"; cat "$out/$name.zip.sha256"
