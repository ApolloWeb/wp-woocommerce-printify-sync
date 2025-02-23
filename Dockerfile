# Use PHP-FPM with Alpine
FROM php:8.2-fpm-alpine

# Install required dependencies
RUN apk add --no-cache git zip unzip curl jq apache2 apache2-utils apache2-proxy apache2-proxy-fcgi

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set environment variable to allow Composer plugins to run as root/super user
ENV COMPOSER_ALLOW_SUPERUSER=1

# Set working directory
WORKDIR /var/www

# Copy all project files into the container
COPY . /var/www/

# Fix Git safe directory error
RUN git config --global --add safe.directory /var/www

# Ensure Composer uses HTTPS instead of SSH
RUN composer config --global github-protocols https

# Install Composer dependencies, including WordPress
RUN composer clear-cache && composer install --prefer-dist --no-progress --working-dir=/var/www --no-interaction --optimize-autoloader --no-dev

# Clone the required plugin repository manually using Git
RUN rm -rf wp-content/plugins/wp-woocommerce-printify-sync && \
    git clone --branch master https://github.com/ApolloWeb/wp-woocommerce-printify-sync.git wp-content/plugins/wp-woocommerce-printify-sync

# Ensure the cloned repo is recognized by Composer
RUN composer dump-autoload

# Ensure correct permissions for WordPress files and create missing directories
RUN mkdir -p /var/www/wp-content && \
    chown -R www-data:www-data /var/www && \
    chmod -R 755 /var/www/wp-content

# Configure Apache
COPY docker/apache/httpd.conf /etc/apache2/httpd.conf
RUN mkdir -p /run/apache2 && chown -R www-data:www-data /run/apache2

# Expose ports for Apache and PHP-FPM
EXPOSE 80
EXPOSE 9000

# Start Apache and PHP-FPM
CMD ["sh", "-c", "httpd -D FOREGROUND & php-fpm -F"]