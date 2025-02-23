#!/bin/sh

# Wait for WordPress to be ready
until wp core is-installed --allow-root; do
    echo "Waiting for WordPress installation..."
    sleep 5
done

# Install and activate WooCommerce if not already installed
if ! wp plugin is-installed woocommerce --allow-root; then
    wp plugin install woocommerce --activate --allow-root
    echo "WooCommerce installed and activated"
fi

# Install and activate CURCY if not already installed
if ! wp plugin is-installed woo-multi-currency --allow-root; then
    wp plugin install woo-multi-currency --activate --allow-root
    echo "WooCommerce Currency Switcher (CURCY) installed and activated"
fi

# Execute the original entrypoint with all arguments
exec docker-entrypoint.sh "$@"