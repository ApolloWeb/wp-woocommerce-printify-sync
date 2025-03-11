# Optimized Multi-Stage Dockerfile for WordPress with PHP 8.2-FPM and Nginx
# Uses multi-stage builds to reduce image size and speed up builds

# Stage 1: Composer Dependencies Builder
FROM composer:2 AS builder
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-progress --no-interaction

# Stage 2: Final PHP-FPM Runtime Container
FROM php:8.2-fpm-alpine AS runtime

# Install required system dependencies and PHP extensions in one step to minimize layers
RUN apk add --no-cache \
    libpng libjpeg-turbo freetype icu-libs curl git mariadb-client \
    linux-headers build-base autoconf bash shadow \
    libpng-dev libjpeg-turbo-dev freetype-dev icu-dev postgresql-dev && \
    docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install gd pdo_mysql mysqli opcache intl bcmath && \
    docker-php-ext-install sockets && \
    pecl install redis && docker-php-ext-enable redis && \
    apk del --purge libpng-dev libjpeg-turbo-dev freetype-dev icu-dev postgresql-dev && \
    rm -rf /var/cache/apk/* /tmp/* /var/tmp/* /var/lib/apt/lists/* /usr/share/man /usr/share/doc /usr/share/info

# Set working directory
WORKDIR /var/www/html

# Copy files from builder, keeping composer dependencies
COPY --from=builder --chown=www-data:www-data . .

RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

EXPOSE 9000

CMD ["php-fpm", "-F"]