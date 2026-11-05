#!/bin/sh
# Prepares a NexCart container. Only the web container (apache) migrates; the scheduler container just runs.
set -e
cd /var/www/html

if [ "$1" = "apache2-foreground" ]; then
    if [ -z "$APP_KEY" ]; then
        echo "APP_KEY is not set. Generate one with:  docker compose run --rm app php artisan key:generate --show" >&2
        exit 1
    fi

    echo "Waiting for the database…"
    i=0
    until php -r '
        $h=getenv("DB_HOST")?:"db"; $p=getenv("DB_PORT")?:"3306";
        try { new PDO("mysql:host=$h;port=$p;dbname=".getenv("DB_DATABASE"), getenv("DB_USERNAME"), getenv("DB_PASSWORD")); exit(0); } catch (Throwable $e) { exit(1); }
    ' 2>/dev/null; do
        i=$((i + 1)); [ "$i" -gt 60 ] && { echo "Database did not become reachable." >&2; exit 1; }
        sleep 2
    done

    # sessions / cache live in files inside the storage volume
    chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
    su www-data -s /bin/sh -c "php artisan storage:link --force >/dev/null 2>&1 || true"
    su www-data -s /bin/sh -c "php artisan migrate --force --no-interaction"
    su www-data -s /bin/sh -c "php artisan nexcart:sync-permissions >/dev/null"
    if [ "$APP_ENV" = "production" ]; then
        su www-data -s /bin/sh -c "php artisan config:cache && php artisan route:cache && php artisan view:cache" >/dev/null
    fi
fi

exec "$@"
