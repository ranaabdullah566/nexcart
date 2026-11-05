#!/usr/bin/env bash
# Create an isolated NexCart deployment for one client: its own folder, secrets, database and storage volume.
# Usage: scripts/provision-client.sh <client-slug> <https://shop.example.com> [port]
set -euo pipefail

slug="${1:-}"; url="${2:-}"; port="${3:-}"
if [[ ! "$slug" =~ ^[a-z0-9][a-z0-9-]{1,30}$ || ! "$url" =~ ^https?://[A-Za-z0-9.-]+(:[0-9]+)?$ ]]; then
  echo "Usage: $0 <client-slug (a-z 0-9 -)> <https://shop.example.com> [port]" >&2; exit 1
fi

root="$(cd "$(dirname "$0")/.." && pwd)"
dir="$root/clients/$slug"
[[ -e "$dir" ]] && { echo "clients/$slug already exists — refusing to overwrite." >&2; exit 1; }

if [[ -z "$port" ]]; then
  port=$(( 20000 + RANDOM % 10000 ))
fi
secret() { openssl rand -hex "${1:-16}"; }

mkdir -p "$dir"
key="base64:$(openssl rand -base64 32)"
cat > "$dir/.env" <<ENV
COMPOSE_PROJECT_NAME=nexcart-$slug
NEXCART_IMAGE=nexcart:latest
APP_NAME="$slug"
APP_ENV=production
APP_DEBUG=false
APP_URL=$url
APP_KEY=$key
APP_PORT=$port
TRUSTED_PROXIES=*

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=nexcart
DB_USERNAME=nexcart
DB_PASSWORD=$(secret 16)
DB_ROOT_PASSWORD=$(secret 16)

SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync

MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=
MAIL_FROM_NAME="$slug"

NEXCART_LICENSE_PUBLIC_KEY=${NEXCART_LICENSE_PUBLIC_KEY:-}
NEXCART_UPDATE_URL=${NEXCART_UPDATE_URL:-}
ENV
chmod 600 "$dir/.env"
cp "$root/docker-compose.yml" "$dir/docker-compose.yml"
sed -i 's|^  build: \.|  # image is built once: docker build -t nexcart:latest .|' "$dir/docker-compose.yml"

cat <<DONE

Client "$slug" prepared in clients/$slug
  1. Build the image once (skip if nexcart:latest already exists):   docker build -t nexcart:latest "$root"
  2. Start it:                                                       (cd clients/$slug && docker compose up -d)
  3. Point your reverse proxy / DNS for $url at 127.0.0.1:$port and turn on HTTPS.
  4. Open $url — the setup wizard creates the owner account.
  5. Issue and send their license key:  php artisan nexcart:license:generate "<Business>" --domain=<their domain>

Secrets are in clients/$slug/.env (mode 600). Back that file up; without APP_KEY encrypted data cannot be read.
DONE
