# WP WooCommerce Printify Sync

[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](https://opensource.org/licenses/MIT)
[![WordPress: 5.6+](https://img.shields.io/badge/WordPress-5.6%2B-blue.svg)](https://wordpress.org/)
[![WooCommerce: 5.0+](https://img.shields.io/badge/WooCommerce-5.0%2B-purple.svg)](https://woocommerce.com/)
[![PHP: 7.3+](https://img.shields.io/badge/PHP-7.3%2B-yellow.svg)](https://php.net/)

A comprehensive WordPress plugin that seamlessly integrates Printify's print-on-demand services with WooCommerce to synchronize products, orders, shipping data, and stock levels.

## Features

- **Complete Printify Integration**: Sync products, orders, shipping data, and stock levels between Printify and WooCommerce
- **Dynamic Shipping & Pricing**: Retrieve and map tiered shipping profiles from Printify to WooCommerce shipping zones
- **Currency Conversion**: Convert USD-based prices to your store's base currency using real-time exchange rates
- **Multi-Provider Support**: Associate products with print provider IDs and allow separate shipping methods for multi-provider orders
- **Order Processing**: Automated order synchronization with external order ID tracking and webhook handling
- **Ticketing System**: Capture refund/reprint requests and admin notifications via an integrated ticketing system
- **Background Processing**: Utilize Action Scheduler for robust background processing with retries and error handling
- **Comprehensive Logging**: Detailed logging system with auto-pruning and export capabilities
- **Security Focus**: Secure storage of API credentials and sensitive data with encryption

## Requirements

- WordPress 5.6 or higher
- WooCommerce 5.0 or higher
- PHP 7.3 or higher
- SSL certificate (recommended for secure API communication)
- Printify account with API access

## Installation

### Manual Installation

1. Download the latest release from the [GitHub repository](https://github.com/ApolloWeb/wp-woocommerce-printify-sync)
2. Upload the plugin files to the `/wp-content/plugins/wp-woocommerce-printify-sync` directory
3. Activate the plugin through the 'Plugins' menu in WordPress

### Via Composer

```bash
composer require apolloweb/wp-woocommerce-printify-sync