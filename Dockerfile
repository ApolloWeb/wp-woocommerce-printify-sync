FROM wordpress:php8.2-fpm

# Set the working directory
WORKDIR /var/www/html

# Get the version number
ENV VERSION=9

# Install dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    wget \
    default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

# Install WP-CLI
RUN curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar \
    && chmod +x wp-cli.phar \
    && mv wp-cli.phar /usr/local/bin/wp

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Create .composer directory and set ownership
RUN mkdir -p /var/www/.composer && chown -R www-data:www-data /var/www/.composer

# Add wp-cli config
RUN mkdir -p /var/www/.wp-cli \
    && chown www-data:www-data /var/www/.wp-cli

# Copy custom php.ini settings
COPY php.ini /usr/local/etc/php/conf.d/custom.ini

# Copy WP Config
COPY wp-config-project.php /var/www/wp-config.php

# Copy the .env file
COPY .env /var/www/html/.env

# Set permissions for the .env file
RUN chmod 660 /var/www/html/.env && chown www-data:www-data /var/www/html/.env

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html

# Switch to www-data user
USER www-data

WORKDIR /var/www/html

# Allow composer/installers plugin
RUN composer config --global --no-plugins allow-plugins.composer/installers true

# Set an environment variable to control running composer as root
ENV COMPOSER_ALLOW_SUPERUSER=1

# Copy the composer.json and composer.lock files
COPY composer.json composer.lock ./

# Install composer dependencies
RUN composer install --no-interaction --prefer-dist --no-progress --no-cache --no-plugins

# Dump autoload
RUN composer dump-autoload --optimize