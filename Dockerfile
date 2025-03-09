# Use PHP 8.2-FPM official image
FROM php:8.2-fpm

# Install required system dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    unzip \
    zip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libxml2-dev \
    mariadb-client \
    redis \
    curl \
    wget \
    git \
    vim \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd mbstring xml pdo pdo_mysql mysqli zip bcmath soap intl opcache \
    && docker-php-ext-enable gd mbstring xml pdo pdo_mysql mysqli zip bcmath soap intl opcache

# Install Redis PHP extension
RUN pecl install redis \
    && docker-php-ext-enable redis

# ✅ Install Composer globally
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set working directory
WORKDIR /var/www

# ✅ Copy only composer files first (Docker caching optimization)
COPY composer.json composer.lock /var/www/

# ✅ Install PHP dependencies before copying the full project (Speeds up builds)
RUN composer install --no-dev --optimize-autoloader --prefer-dist

# ✅ Copy the rest of the application files AFTER dependencies are installed
COPY . /var/www

# Ensure correct permissions
RUN chown -R www-data:www-data /var/www

# Expose PHP-FPM port
EXPOSE 9000

# Start PHP-FPM
CMD ["php-fpm"]
