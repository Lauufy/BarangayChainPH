
# Stage 1: Build frontend assets
FROM node:22-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY resources ./resources
COPY vite.config.js ./
COPY public ./public
RUN npm run build

# Stage 2: Build Laravel application
FROM php:8.4-apache

RUN apt-get update && apt-get install -y \
    git unzip libpq-dev libzip-dev \
    libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    libonig-dev curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_pgsql mbstring bcmath gd zip opcache \
    && a2enmod rewrite headers \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

# Copy the compiled frontend assets
COPY --from=frontend /app/public/build ./public/build

RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && mkdir -p storage/framework/cache \
       storage/framework/sessions \
       storage/framework/views \
       bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

# Configure Apache to serve Laravel's public directory
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/000-default.conf \
    /etc/apache2/apache2.conf

# Render provides the port through the PORT environment variable
RUN printf '%s\n' \
    '#!/bin/sh' \
    'set -e' \
    'PORT="${PORT:-10000}"' \
    'sed -ri "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf' \
    'sed -ri "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf' \
    'exec apache2-foreground' \
    > /usr/local/bin/start-render.sh \
    && chmod +x /usr/local/bin/start-render.sh

EXPOSE 10000

CMD ["sh", "-c", "php artisan config:clear && /usr/local/bin/start-render.sh"]
