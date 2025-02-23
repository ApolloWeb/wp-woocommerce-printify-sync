FROM wordpress:php8.2-fpm

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


# Add wp-cli config
RUN mkdir -p /var/www/.wp-cli \
    && chown www-data:www-data /var/www/.wp-cli

# Copy custom php.ini settings
COPY php.ini /usr/local/etc/php/conf.d/custom.ini

# Copy WP Config
COPY wp-config-project.php /var/www/wp-config.php

# Copy Composer files
COPY composer.json /var/www/composer.json
COPY composer.lock /var/www/composer.lock

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html

# Switch to www-data user
USER www-data

WORKDIR /var/www/html

RUN composer install --no-interaction --prefer-dist --no-progress
RUN composer dump-autoload --optimize