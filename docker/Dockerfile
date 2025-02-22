# Use PHP-FPM with Alpine 3.21
FROM php:8.2-fpm-alpine

# ✅ Ensure the correct Alpine repositories are used
RUN echo "https://dl-cdn.alpinelinux.org/alpine/latest-stable/main" > /etc/apk/repositories && \
    echo "https://dl-cdn.alpinelinux.org/alpine/latest-stable/community" >> /etc/apk/repositories && \
    apk update --no-cache

# ✅ Install PHP 8.2 packages (Alpine provides php82-*)
RUN apk add --no-cache \
    php82 \
    php82-fpm \
    php82-openssl \
    php82-phar \
    php82-pdo \
    php82-pdo_mysql \
    php82-tokenizer \
    php82-zlib \
    php82-dom \
    php82-session \
    php82-mbstring \
    php82-ctype \
    php82-simplexml \
    php82-curl \
    php82-soap \
    php82-bcmath \
    php82-xml \
    php82-zip \
    php82-exif \
    php82-gd \
    php82-opcache \
    zip \
    unzip \
    wget \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    libzip-dev \
    oniguruma-dev \
    bash \
    coreutils \
    curl

# ✅ Set working directory
WORKDIR /var/www

# ✅ Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# ✅ Copy Composer dependencies
COPY composer.json ./
RUN composer install --prefer-dist --no-progress --no-dev

# ✅ Set permissions
RUN chown -R www-data:www-data /var/www && chmod -R 755 /var/www

# ✅ Expose PHP-FPM port
EXPOSE 9000

# ✅ Start PHP-FPM
CMD ["php-fpm", "-F"]
