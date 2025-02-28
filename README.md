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
      .gitignore
      .header_exclude
      .php-cs-fixer.php
      .phpcs.xml
      .wp-env.json
      LICENSE
      README.md
      README_TEMPLATE.txt
      admin/Admin.php
      admin/assets/css/admin-styles.css
      admin/assets/js/admin-script.js
      admin/assets/js/admin.js
      admin/assets/js/products.js
      admin/assets/js/shops.js
      admin/includes/Helper.php
      admin/templates/products-section.php
      admin/templates/settings-page.php
      admin/templates/shops-section.php
      composer.json
      composer.lock
      eslint.config.js
      includes/Api.php
      includes/Autoloader.php
      includes/Helpers/CategoriesHelper.php
      includes/Helpers/ImagesHelper.php
      includes/Helpers/TagsHelper.php
      includes/Helpers/VariantsHelper.php
      includes/ImageUpload.php
      includes/Logger.php
      includes/PrintifyAPI.php
      includes/ProductImport.php
      includes/ProductImportCron.php
      includes/ProductImporter.php
      package-lock.json
      package.json
      phpcs.xml.dist
      phpstan.neon
      phpunit.xml.dist
      update-readme.js
      wp-woocommerce-printify-sync.php
<!-- FILE-STRUCTURE-END -->

## File Descriptions
<!-- FILE-DESCRIPTIONS-START -->
- **.gitignore**: Description of .gitignore
- **.header_exclude**: Description of .header_exclude
- **.php-cs-fixer.php**: Description of .php-cs-fixer.php
- **.phpcs.xml**: Description of .phpcs.xml
- **.wp-env.json**: Description of .wp-env.json
- **LICENSE**: Description of LICENSE
- **README.md**: Description of README.md
- **README_TEMPLATE.txt**: Description of README_TEMPLATE.txt
- **Admin.php**: Description of Admin.php
- **admin-styles.css**: Description of admin-styles.css
- **admin-script.js**: Description of admin-script.js
- **admin.js**: Description of admin.js
- **products.js**: Description of products.js
- **shops.js**: Description of shops.js
- **Helper.php**: Description of Helper.php
- **products-section.php**: Description of products-section.php
- **settings-page.php**: Description of settings-page.php
- **shops-section.php**: Description of shops-section.php
- **composer.json**: Description of composer.json
- **composer.lock**: Description of composer.lock
- **eslint.config.js**: Description of eslint.config.js
- **Api.php**: Description of Api.php
- **Autoloader.php**: Description of Autoloader.php
- **CategoriesHelper.php**: Description of CategoriesHelper.php
- **ImagesHelper.php**: Description of ImagesHelper.php
- **TagsHelper.php**: Description of TagsHelper.php
- **VariantsHelper.php**: Description of VariantsHelper.php
- **ImageUpload.php**: Description of ImageUpload.php
- **Logger.php**: Description of Logger.php
- **PrintifyAPI.php**: Description of PrintifyAPI.php
- **ProductImport.php**: Description of ProductImport.php
- **ProductImportCron.php**: Description of ProductImportCron.php
- **ProductImporter.php**: Description of ProductImporter.php
- **package-lock.json**: Description of package-lock.json
- **package.json**: Description of package.json
- **phpcs.xml.dist**: Description of phpcs.xml.dist
- **phpstan.neon**: Description of phpstan.neon
- **phpunit.xml.dist**: Description of phpunit.xml.dist
- **update-readme.js**: Description of update-readme.js
- **wp-woocommerce-printify-sync.php**: Description of wp-woocommerce-printify-sync.php
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