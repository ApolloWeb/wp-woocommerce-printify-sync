# WP WooCommerce Printify Sync

A WordPress plugin to sync products between Printify and WooCommerce.

## Features

- Sync products from Printify to WooCommerce
- Automatically handles product variations and images
- Manage Printify products directly from WordPress

## Installation

1. Upload the plugin files to the `/wp-content/plugins/wp-woocommerce-printify-sync` directory
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to Printify Sync > Settings to configure your API credentials

## Configuration

1. Get your API key from your Printify account (Account → API keys)
2. Enter the API key in the plugin settings
3. Click "Fetch Shops" to get your shop ID
4. Click "Test Connection" to verify your credentials

## Printify API Reference

This plugin uses the Printify API v1. For full documentation, visit:
https://developers.printify.com/

### Key Endpoints

The plugin uses the following API endpoints:

- `shops.json` - Get a list of shops
- `shops/{shop_id}/products.json` - Get products for a shop
- `shops/{shop_id}/products/{product_id}.json` - Get a specific product
