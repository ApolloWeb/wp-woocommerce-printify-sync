# WP WooCommerce Printify Sync

Synchronize your WooCommerce products with Printify effortlessly.

**Current Version:** 1.0.0  
**Last Updated:** 2025-03-15 18:38:38  
**Maintainer:** ApolloWeb  

## 🚀 Quick Links

- [Documentation](https://github.com/ApolloWeb/wp-woocommerce-printify-sync/wiki)
- [Support](mailto:hello@apollo-web.co.uk)
- [Slack Channel](https://apollowebworkspace.slack.com/archives/C08FLP5Q8FL)
- [Changelog](CHANGELOG.md)

## 📋 Requirements

- WordPress 5.8+
- WooCommerce 5.0+
- PHP 7.4+
- MySQL 5.7+ or MariaDB 10.2+

## 🔧 Installation

1. Upload `wp-woocommerce-printify-sync` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to WooCommerce → Printify Sync to configure

## ⚙️ Configuration

1. Obtain your Printify API key from [Printify Dashboard](https://printify.com/dashboard)
2. Go to WooCommerce → Printify Sync → Settings
3. Enter your API key and save changes
4. Configure product sync options

## 🎯 Features

- Automated product synchronization
- Two-way inventory updates
- Product type categorization
- Image synchronization
- Order status management
- Detailed sync logs
- Data cleanup tools

## 🔄 Usage

### Basic Sync
```php
// Trigger manual sync
do_action('wpwps_sync_products');