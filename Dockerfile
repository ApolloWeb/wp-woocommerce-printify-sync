FROM wordpress:php8.2-fpm

# Install required PHP extensions and utilities
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    wget \
    && docker-php-ext-install zip pdo pdo_mysql

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install Xdebug
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# Create directory for PHP logs
RUN mkdir -p /var/log/php && \
    chown www-data:www-data /var/log/php

# Copy php.ini
COPY php.ini /usr/local/etc/php/conf.d/custom.ini

# Install WP-CLI
RUN curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar \
    && chmod +x wp-cli.phar \
    && mv wp-cli.phar /usr/local/bin/wp

# Copy composer files
COPY composer.json composer.lock /var/www/html/

# Install dependencies
WORKDIR /var/www/html
RUN composer install --no-dev --optimize-autoloader

# Copy plugin files
COPY wp-content/plugins/wp-woocommerce-printify-sync /var/www/html/wp-content/plugins/wp-woocommerce-printify-sync

# Copy and set up entrypoint script
COPY docker-entrypoint-extras.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint-extras.sh \
    && sed -i 's/\r//g' /usr/local/bin/docker-entrypoint-extras.sh