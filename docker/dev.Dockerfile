# Use PHP-FPM with Alpine
FROM php:8.2-fpm-alpine

# ✅ Install required dependencies
RUN apk add --no-cache git zip unzip curl jq

# ✅ Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# ✅ Set working directory
WORKDIR /var/www

# ✅ Copy all project files into the container
COPY . /var/www/

# ✅ Fix Git safe directory error
RUN git config --global --add safe.directory /var/www

# ✅ Ensure Composer uses HTTPS instead of SSH
RUN composer config --global github-protocols https

# ✅ Clone the required repository manually using Git (remove existing directory first)
RUN rm -rf plugins/wp-woocommerce-printify-sync && \
    git clone --branch mercury https://github.com/ApolloWeb/wp-woocommerce-printify-sync.git plugins/wp-woocommerce-printify-sync

# ✅ Ensure the cloned repo is recognized by Composer
RUN composer dump-autoload

# ✅ Install Composer dependencies
RUN composer clear-cache && composer install --prefer-dist --no-progress

# ✅ Ensure correct permissions for WordPress files
RUN chown -R www-data:www-data /var/www && chmod -R 755 /var/www/wp-content

# ✅ Expose PHP-FPM port
EXPOSE 9000

# ✅ Start PHP-FPM
CMD ["php-fpm", "-F"]
