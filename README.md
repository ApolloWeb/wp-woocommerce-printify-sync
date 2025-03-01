# WP WooCommerce Printify Sync

WP WooCommerce Printify Sync is a plugin that synchronizes products, orders, and inventory from Printify to WooCommerce.

## Features

- Automatic Product Sync
- Webhook-Based Order & Stock Management
- Optimized Image Handling
- Efficient Import & Caching
- Security Enhancements
- WooCommerce-Styled Admin UI
- Robust Error Handling & Logging

## Installation

1. Upload the plugin files to the `/wp-content/plugins/wp-woocommerce-printify-sync` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Configure the plugin settings on the "Printify Sync" admin page.

## Usage

- **Settings**: Configure your Printify API key, Shop ID, and Sync Frequency.
- **Product Sync**: Manually trigger product synchronization.
- **Logs**: View logs of recent sync operations.

## Development

- All JavaScript is in separate files under the `admin/js` and `public/js` directories.
- HTML template files are in `admin/partials`.
- All files adhere to PSR-12 standards and follow the namespace `ApolloWeb\WPWooCommercePrintifySync`.

## License

MIT License.