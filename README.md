# WP WooCommerce Printify Sync

**Plugin Name:** WP WooCommerce Printify Sync  
**Plugin URI:** [https://github.com/ApolloWeb/wp-woocommerce-printify-sync](https://github.com/ApolloWeb/wp-woocommerce-printify-sync)  
**Description:** Integrates Printify with WooCommerce for seamless synchronization of product data, orders, categories, images, and SEO metadata.  
**Version:** 1.0.0  
**Author:** Rob Owen  
**Author URI:** [https://github.com/ApolloWeb](https://github.com/ApolloWeb)  
**Text Domain:** wp-woocommerce-printify-sync  

## Executive Summary

The WooCommerce Printify Sync plugin integrates Printify with WooCommerce for seamless synchronization of product data, orders, categories, images, and SEO metadata. The plugin facilitates the automatic import of products, syncing product updates, managing stock levels, and optimizing images. Additionally, the plugin handles order synchronization between WooCommerce and Printify. The admin page allows users to manage Printify shops, select a shop for importing products, and configure Cloudflare for image storage.

## Table of Contents

1. [Installation](#installation)
2. [Configuration](#configuration)
3. [Usage](#usage)
4. [Core Functions and API Endpoints](#core-functions-and-api-endpoints)
5. [License](#license)

## Installation

1. Download the plugin zip file from the [GitHub repository](https://github.com/ApolloWeb/wp-woocommerce-printify-sync).
2. In your WordPress admin panel, navigate to Plugins > Add New.
3. Click the "Upload Plugin" button at the top of the page.
4. Choose the downloaded zip file and click "Install Now".
5. Activate the plugin through the 'Plugins' menu in WordPress.

## Configuration

1. Navigate to the plugin settings page under Settings > Printify Sync.
2. Enter your Printify API key.
3. Select the shop you want to sync with WooCommerce.
4. Configure other settings as needed, such as test/live mode and image storage options.

## Usage

1. To manually sync products, navigate to Settings > Printify Sync and click the "Sync Products" button.
2. The plugin will automatically sync product data, orders, categories, images, and SEO metadata based on the configured settings.

## Core Functions and API Endpoints

### Printify API Endpoints

- **GET /shops**: Retrieves the list of available Printify shops.
- **GET /shops/{shop_id}/products.json**: Retrieves products for a specified shop.
- **GET /products/{product_id}.json**: Retrieves details of a single product.
- **GET /products/{product_id}/stock_levels.json**: Retrieves stock levels for a product.
- **GET /products/{product_id}/variants.json**: Fetches variants for a product.
- **GET /products/{product_id}/tags.json**: Fetches tags for a product.
- **GET /products/{product_id}/categories.json**: Fetches categories for a product.

### WooCommerce Product and Order Sync

- Create or update products using `wp_insert_post()`.
- Handle product variations using `WC_Product_Variation()`.
- Sync WooCommerce orders with Printify for fulfillment.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.