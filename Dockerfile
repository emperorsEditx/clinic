# syntax=docker/dockerfile:1.7
# Production image: PHP 8.3 FPM + NGINX (serversideup/php), listens on 8080 as an unprivileged user.

ARG PHP_IMAGE=serversideup/php:8.3-fpm-nginx

############################
# 1. PHP dependencies
############################
FROM ${PHP_IMAGE} AS vendor
USER root
RUN install-php-extensions gd intl bcmath
USER www-data
WORKDIR /var/www/html
COPY --chown=www-data:www-data composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist --no-scripts --no-autoloader

############################
# 2. Front-end assets
############################
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json .npmrc ./
RUN npm ci --ignore-scripts --no-audit --no-fund
COPY resources ./resources
COPY app/Enums ./app/Enums
COPY vite.config.js ./
# Tailwind scans the pagination views shipped in vendor.
COPY --from=vendor /var/www/html/vendor/laravel/framework/src/Illuminate/Pagination/resources/views ./vendor/laravel/framework/src/Illuminate/Pagination/resources/views
RUN npm run build

############################
# 3. Runtime
############################
FROM ${PHP_IMAGE} AS runtime
USER root
# gd/intl/bcmath for PDFs + Excel; mariadb-client provides mysqldump for spatie/laravel-backup.
RUN install-php-extensions gd intl bcmath \
    && apt-get update \
    && apt-get install -y --no-install-recommends mariadb-client \
    && rm -rf /var/lib/apt/lists/*

ENV PHP_OPCACHE_ENABLE=1 \
    PHP_UPLOAD_MAX_FILE_SIZE=12M \
    PHP_POST_MAX_SIZE=14M \
    PHP_MEMORY_LIMIT=256M \
    AUTORUN_ENABLED=true \
    AUTORUN_LARAVEL_MIGRATION=false \
    AUTORUN_LARAVEL_STORAGE_LINK=true

USER www-data
WORKDIR /var/www/html
COPY --chown=www-data:www-data . .
COPY --chown=www-data:www-data --from=vendor /var/www/html/vendor ./vendor
COPY --chown=www-data:www-data --from=assets /app/public/build ./public/build
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative --no-interaction \
    && mkdir -p storage/app/private storage/app/public storage/backups storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache

ARG APP_VERSION=dev
ENV APP_VERSION=${APP_VERSION}
LABEL org.opencontainers.image.title="Clinic Management System" \
      org.opencontainers.image.source="https://github.com/emperorsEditx/clinic"

EXPOSE 8080
