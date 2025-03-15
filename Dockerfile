# Use official PHP-FPM image for performance
FROM php:8.2-fpm

# Install dependencies
RUN apt-get update && apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libwebp-dev \
    libxml2-dev \
    libzip-dev \
    libonig-dev \
    nginx \
    supervisor \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    mysqli \
    zip \
    exif \
    opcache \
    intl \
    gd \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copy the project files
COPY . /var/www/html

# Set working directory
WORKDIR /var/www/html

# Copy Nginx configuration files
COPY nginx/conf.d/default.conf /etc/nginx/conf.d/default.conf
COPY nginx/conf.d /etc/nginx/conf.d

# Copy PHP configuration files
COPY php/php.ini /usr/local/etc/php/php.ini
COPY php/php-fpm.conf /usr/local/etc/php-fpm.conf

# Copy Supervisor configuration
COPY etc/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Set permissions before running Composer
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 775 /var/www/html && \
    find /var/www/html -type d -exec chmod g+s {} \;

# Install PHP dependencies
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Set permissions again after running Composer
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 775 /var/www/html && \
    find /var/www/html -type d -exec chmod g+s {} \; && \
    mkdir -p /var/log/nginx && \
    chown -R www-data:www-data /var/log/nginx && \
    mkdir -p /var/run && \
    chown -R www-data:www-data /var/run

# Define volumes
VOLUME /var/www/html/wp
VOLUME /var/www/html/wp-content
VOLUME /var/www/html/wp-config.php
VOLUME /var/www/html/index.php
VOLUME /var/www/html/.env

# Expose port 80
EXPOSE 80

# Start supervisord to manage nginx and php-fpm
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]