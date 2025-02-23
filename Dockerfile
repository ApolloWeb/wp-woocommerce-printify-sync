ARG CACHEBUST=1

FROM wordpress:php8.2-fpm

# Install additional performance dependencies
FROM wordpress:php8.2-fpm

# Install additional performance dependencies
RUN apt-get update && apt-get install -y \
    redis-server \
    jpegoptim optipng pngquant gifsicle \
    && if ! php -m | grep -q 'opcache'; then docker-php-ext-install opcache && docker-php-ext-enable opcache; fi \
    && if ! php -m | grep -q 'redis'; then pecl install redis && docker-php-ext-enable redis; fi \
    && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /var/www/html

# Configure OPcache
RUN echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.interned_strings_buffer=8" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=4000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.revalidate_freq=60" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.enable_cli=1" >> /usr/local/etc/php/conf.d/opcache.ini

# Ensure correct file permissions
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html/wp-content
