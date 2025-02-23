# Use PHP-FPM with Alpine
FROM php:8.2-fpm-alpine

# ✅ Install required dependencies
RUN apk add --no-cache git zip unzip curl jq

# ✅ Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# ✅ Set working directory
WORKDIR /var/www

# ✅ Copy project files *before* running Composer
COPY . /var/www/

# ✅ Fix Git safe directory error
RUN git config --global --add safe.directory /var/www

# ✅ Ensure Git uses HTTPS instead of SSH
RUN git config --global url."https://github.com/".insteadOf "git@github.com:"

# ✅ Clone the required repository manually using Git
RUN rm -rf plugins/wp-woocommerce-printify-sync && \
    git clone --branch mercury https://github.com/ApolloWeb/wp-woocommerce-printify-sync.git plugins/wp-woocommerce-printify-sync

# ✅ Ensure `composer.json` exists before running Composer commands
WORKDIR /var/www

# ✅ Allow necessary plugins for Composer
RUN composer config --no-plugins allow-plugins.johnpbloch/wordpress-core-installer true && \
    composer config --no-plugins allow-plugins.composer/installers true

# ✅ Install Composer dependencies
RUN composer install --prefer-dist --no-progress

# ✅ Ensure correct permissions for WordPress files
RUN chown -R www-data:www-data /var/www && chmod -R 755 /var/www

# ✅ Expose PHP-FPM port
EXPOSE 9000

# ✅ Start PHP-FPM
CMD ["php-fpm", "-F"]
