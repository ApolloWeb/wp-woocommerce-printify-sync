# Use the official WordPress image with PHP-FPM
FROM wordpress:php8.2-fpm

# Install required dependencies
RUN apt-get update && apt-get install -y git zip unzip curl jq

# Set working directory
WORKDIR /var/www/html

# Clone the required plugin repository manually using Git
RUN rm -rf wp-content/plugins/wp-woocommerce-printify-sync && \
    git clone --branch master https://github.com/ApolloWeb/wp-woocommerce-printify-sync.git wp-content/plugins/wp-woocommerce-printify-sync

# Ensure correct permissions for WordPress files and create missing directories
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html/wp-content