# WP WooCommerce Printify Sync

## Overview
`wp-woocommerce-printify-sync` is a WordPress plugin that integrates WooCommerce with Printify, allowing seamless product synchronization.

## File Structure
The plugin's files are structured as follows:

```
wp-content/plugins/wp-woocommerce-printify-sync/
      │── wp-woocommerce-printify-sync.php
      │
      ├── admin/
      │   ├── Admin.php
      │   ├── assets/
      │   │   ├── js/
      │   │   │   ├── admin-script.js
      │   │   │   ├── products.js
      │   │   │   ├── shops.js
      │   │   ├── css/
      │   │   │   ├── admin-styles.css
      │   ├── templates/
      │   │   ├── products-section.php
      │   │   ├── settings-page.php
      │   │   ├── shops-section.php
      │
      ├── includes/
      │   ├── Api.php
      │   ├── Autoloader.php
```

## File Descriptions
- **wp-woocommerce-printify-sync.php**: The main plugin file that initializes the plugin.
- **Admin.php**: Handles the admin panel functionality.
- **assets/**:
  - **js/**:
    - `admin-script.js`: JavaScript file for admin interactions.
    - `products.js`: Handles product-related JavaScript functionality.
    - `shops.js`: Manages shop-related JavaScript actions.
  - **css/**:
    - `admin-styles.css`: Admin panel styles.
- **templates/**:
  - `products-section.php`: Template for the products section.
  - `settings-page.php`: Template for the plugin settings page.
  - `shops-section.php`: Template for managing shops.
- **includes/**:
  - `Api.php`: Handles API calls and interactions.
  - `Autoloader.php`: Manages class autoloading for the plugin.

## Installation
1. Upload the `wp-woocommerce-printify-sync` folder to the `wp-content/plugins/` directory.
2. Activate the plugin via the WordPress admin panel.

## Usage
- Navigate to **WooCommerce > Printify Sync** in the WordPress admin to configure settings and manage product synchronization.

## License
This plugin is licensed under the [MIT License](LICENSE).

