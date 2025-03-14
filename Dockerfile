# Use a multi-stage build to optimize the final image
FROM php:8.2-fpm AS builder

ENV DEBIAN_FRONTEND=noninteractive

# Install dependencies for PHP extensions and Composer
RUN apt-get update && apt-get install -y --no-install-recommends \
    libbz2-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zlib1g-dev \
    libwebp-dev \
    libxml2-dev \
    libxslt-dev \
    libicu-dev \
    libmagickwand-dev \
    libmemcached-dev \
    libonig-dev \
    libcurl4-openssl-dev \
    libssl-dev \
    unzip \
    curl \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        bcmath \
        bz2 \
        calendar \
        exif \
        gd \
        gettext \
        intl \
        mbstring \
        mysqli \
        opcache \
        pcntl \
        pdo \
        pdo_mysql \
        soap \
        sockets \
        sysvmsg \
        sysvsem \
        sysvshm \
        xml \
        xsl \
        zip \
    && pecl install imagick redis memcached \
    && docker-php-ext-enable imagick redis memcached \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set working directory
WORKDIR /var/www/html

# Copy the application source code
COPY . .

# Persist the vendor directory between builds
RUN composer install --no-dev --optimize-autoloader

# Stage 2: Production image
FROM php:8.2-fpm

ENV DEBIAN_FRONTEND=noninteractive

# Install only required runtime dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    libmemcached11 \
    libmemcachedutil2 \
    libmagickwand-6.q16-6 \
    libxslt1.1 \
    libicu72 \
    libzip4 \
    libwebp7 \
    libpng16-16 \
    libjpeg62-turbo \
    libfreetype6 \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Copy built extensions and configurations from builder stage
COPY --from=builder /usr/local/lib/php/extensions /usr/local/lib/php/extensions
COPY --from=builder /usr/local/etc/php/conf.d /usr/local/etc/php/conf.d
COPY --from=builder /var/www/html /var/www/html

# Persist vendor directory using Docker volume
VOLUME /var/www/html/vendor
VOLUME /var/www/html/wp
VOLUME /var/www/html/wp-content
VOLUME /var/www/html/wp-config.php
VOLUME /var/www/html/index.php

WORKDIR /var/www/html
EXPOSE 9000
CMD ["php-fpm"]
