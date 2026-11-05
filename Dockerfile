# syntax=docker/dockerfile:1
# NexCart production image: Apache + PHP 8.3. One image serves every client; each client gets its own
# database, storage volume and .env (see scripts/provision-client.sh).

FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --no-scripts --optimize-autoloader

FROM php:8.3-apache AS app

RUN apt-get update \
 && apt-get install -y --no-install-recommends libzip-dev libpng-dev libjpeg-dev libwebp-dev libfreetype6-dev unzip curl \
 && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
 && docker-php-ext-install -j"$(nproc)" gd zip pdo_mysql opcache \
 && a2enmod rewrite headers remoteip \
 && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY docker/php.ini /usr/local/etc/php/conf.d/nexcart.ini
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/entrypoint.sh /usr/local/bin/nexcart-entrypoint
RUN chmod +x /usr/local/bin/nexcart-entrypoint

WORKDIR /var/www/html
COPY --chown=www-data:www-data . .
COPY --from=vendor --chown=www-data:www-data /app/vendor ./vendor
RUN cp .env.example .env.docker-fallback \
 && mkdir -p storage/app/public storage/app/private storage/app/backups storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache \
 && su www-data -s /bin/sh -c "php artisan package:discover --ansi"

VOLUME ["/var/www/html/storage"]
EXPOSE 80
HEALTHCHECK --interval=30s --timeout=5s --start-period=40s --retries=3 CMD curl -fsS http://localhost/up || exit 1

ENTRYPOINT ["nexcart-entrypoint"]
CMD ["apache2-foreground"]
