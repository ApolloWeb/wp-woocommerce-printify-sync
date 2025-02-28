# WP WooCommerce Printify Sync

## Overview
`wp-woocommerce-printify-sync` is a WordPress plugin that integrates WooCommerce with Printify, allowing seamless product synchronization.

## Features

*   **Automatic Product Sync:** Automatically sync products from Printify to your WooCommerce store.
*   **Inventory Management:** Keep your WooCommerce inventory up-to-date with Printify's stock levels.
*   **Order Fulfillment:** Automatically send orders to Printify for fulfillment.
*   **Customizable Settings:** Configure the plugin to match your specific needs.

## File Structure
The plugin's files are structured as follows:

<!-- FILE-STRUCTURE-START -->
- **./**
  - **includes/**
    - ProductImporter.php
    - PrintifyAPI.php
    - Api.php
    - Autoloader.php
    - ProductImportCron.php
    - ImageUpload.php
    - Logger.php
    - ProductImport.php
    - **Helpers/**
      - VariantsHelper.php
      - ImagesHelper.php
      - TagsHelper.php
      - CategoriesHelper.php
  - **admin/**
    - Admin.php
    - **includes/**
      - Helper.php
    - **templates/**
      - shops-section.php
      - products-section.php
      - settings-page.php
    - **assets/**
      - **css/**
        - admin-styles.css
      - **js/**
        - products.js
        - admin.js
        - shops.js
        - admin-script.js

<!-- FILE-STRUCTURE-END -->

## File Descriptions
<!-- FILE-DESCRIPTIONS-START -->
- **./includes/ProductImporter.php**: File handles importing products: parsing, validating, and saving data to database.
- **./includes/PrintifyAPI.php**: File handles Printify API integration for printing products.
- **./includes/Api.php**: File handles API requests, including authentication, validation, and response generation for various endpoints.
- **./includes/Autoloader.php**: Autoloader class: loads and registers classes and interfaces dynamically.
- **./includes/Helpers/VariantsHelper.php**: File handles actions related to product variants: creation, retrieval, update, and deletion.
- **./includes/Helpers/ImagesHelper.php**: This file handles image manipulation functions using PHP. It includes actions like resizing, cropping, and optimizing images
- **./includes/Helpers/TagsHelper.php**: File handles actions related to generating and parsing tags for content.
- **./includes/Helpers/CategoriesHelper.php**: File handles actions related to categories: fetching, creating, updating, and deleting categories.
- **./includes/ProductImportCron.php**: This file handles importing products from external sources through a cron job.
- **./includes/ImageUpload.php**: Handles image uploading, resizing, and saving.
- **./includes/Logger.php**: Class Logger: logs messages, errors, and events to a file.
- **./includes/ProductImport.php**: File handles importing products: parsing CSV data, validating, creating/updating products in database.
- **./admin/includes/Helper.php**: File handles helper functions for admin tasks: user authentication, data validation, error handling.
- **./admin/Admin.php**: File handles admin actions: user management, settings modification, data retrieval.
- **./admin/templates/shops-section.php**: Template for managing shops: display, create, edit, delete, and search.
- **./admin/templates/products-section.php**: Template for displaying products section with actions like loop, display, and styling.
- **./admin/templates/settings-page.php**: This file displays and saves settings for the admin page.
- **./admin/assets/css/admin-styles.css**: Styles for admin dashboard interface elements. Styling buttons, forms, tables, and navigation.
- **./admin/assets/js/products.js**: JavaScript file handling product-related actions like adding, deleting, and updating products.
- **./admin/assets/js/admin.js**: JavaScript file for admin functionalities: user management, settings update, form validation, error handling.
- **./admin/assets/js/shops.js**: JS file handling shop actions: add, edit, delete, and display products and categories.
- **./admin/assets/js/admin-script.js**: JavaScript file for admin actions: form validation, AJAX requests, user interactions.
<!-- FILE-DESCRIPTIONS-END -->

## Installation
1.  Upload the `wp-woocommerce-printify-sync` folder to the `wp-content/plugins/` directory.
2.  Activate the plugin via the WordPress admin panel.

## Usage
*   Navigate to **WooCommerce > Printify Sync** in the WordPress admin to configure settings and manage product synchronization.
*   Configure the plugin settings under **WooCommerce > Printify Sync > Settings**.
*   Sync products from Printify under **WooCommerce > Printify Sync > Products**.

## Contributing
Contributions are welcome! Please read the [CONTRIBUTING.md](CONTRIBUTING.md) file for more information.

## License
This plugin is licensed under the [MIT License](LICENSE).