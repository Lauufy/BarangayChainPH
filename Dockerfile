
# Stage 1: Build frontend assets
FROM node:24-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY resources ./resources
COPY vite.config.js ./
COPY public ./public

RUN npm run build


# Stage 2: Install blockchain production dependencies
FROM node:22-slim AS blockchain

WORKDIR /blockchain

COPY blockchain/package.json blockchain/package-lock.json ./
RUN npm ci --omit=dev


# Stage 3: Build Laravel application
FROM php:8.4.27-apache

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public \
    COMPOSER_ALLOW_SUPERUSER=1 \
    TESSERACT_PATH=/usr/bin/tesseract

# Install system dependencies, OCR engine and PHP extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    curl \
    ca-certificates \
    tesseract-ocr \
    tesseract-ocr-eng \
    libpq-dev \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libonig-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql \
        mbstring \
        bcmath \
        gd \
        zip \
        opcache \
    && a2enmod rewrite headers \
    && tesseract --version \
    && tesseract --list-langs \
    && test -x /usr/bin/tesseract \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*


# PHP security hardening
RUN printf '%s\n' \
    'session.upload_progress.enabled = Off' \
    'expose_php = Off' \
    'display_errors = Off' \
    'log_errors = On' \
    'session.cookie_httponly = On' \
    'session.cookie_secure = On' \
    'session.cookie_samesite = Lax' \
    > /usr/local/etc/php/conf.d/zz-production.ini


# OPcache production settings
RUN printf '%s\n' \
    'opcache.enable = 1' \
    'opcache.memory_consumption = 128' \
    'opcache.max_accelerated_files = 10000' \
    'opcache.validate_timestamps = 0' \
    > /usr/local/etc/php/conf.d/zz-opcache.ini


# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html


# Copy Laravel application
COPY . .


# Copy blockchain runtime and dependencies
COPY --from=blockchain /usr/local/bin/node /usr/local/bin/node
COPY --from=blockchain /blockchain/node_modules ./blockchain/node_modules


# Copy compiled frontend assets
COPY --from=frontend /app/public/build ./public/build


# Install production dependencies and prepare Laravel directories
RUN composer install \
    --no-dev \
    --prefer-dist \
    --optimize-autoloader \
    --no-interaction \
    --no-progress \
    && mkdir -p \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data \
        storage \
        bootstrap/cache \
    && chmod -R ug+rwX \
        storage \
        bootstrap/cache


# Configure Apache document root
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
        /etc/apache2/sites-available/*.conf \
        /etc/apache2/apache2.conf \
        /etc/apache2/conf-available/*.conf \
    && printf '%s\n' \
        '<Directory ${APACHE_DOCUMENT_ROOT}>' \
        '    AllowOverride All' \
        '    Require all granted' \
        '    Options -Indexes' \
        '</Directory>' \
        > /etc/apache2/conf-available/laravel.conf \
    && printf '%s\n' \
        'ServerName localhost' \
        'ServerTokens Prod' \
        'ServerSignature Off' \
        > /etc/apache2/conf-available/server-basics.conf \
    && a2enconf laravel server-basics


# Render startup script
RUN printf '%s\n' \
    '#!/bin/sh' \
    'set -eu' \
    'PORT="${PORT:-10000}"' \
    'sed -ri "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf' \
    'sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf' \
    'php artisan config:clear' \
    'php artisan migrate --force' \
    'exec apache2-foreground' \
    > /usr/local/bin/start-render.sh \
    && chmod +x /usr/local/bin/start-render.sh


EXPOSE 10000

CMD ["/usr/local/bin/start-render.sh"]
