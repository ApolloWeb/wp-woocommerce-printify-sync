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
      .vscode/settings.json
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
      debug.log
      delete_workflows.sh
      error.log
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
- **.gitignore**: A file specifying folders and files to be ignored by Git, including dependency folders and README.md.
- **.header_exclude**: A JavaScript file for updating the README document.
- **.php-cs-fixer.php**: "This file configures the PHP CS Fixer tool, allowing risky rules and setting specific rules for code formatting."
- **.phpcs.xml**: "Configuration file for PHP_CodeSniffer specifying rules for WordPress coding standards."
- **.vscode/settings.json**: "Configuration settings for the CodeGPT API in Visual Studio Code."
- **.wp-env.json**: "Configuration file for WordPress environment specifying core version and plugins."
- **LICENSE**: "This file contains the MIT License for ApolloWeb, granting permissions for use of their software."
- **README.md**: "A README file for WP WooCommerce Printify Sync, a WordPress plugin that integrates WooCommerce with Printify for product synchronization."
- **README_TEMPLATE.txt**: "A README file for the WP WooCommerce Printify Sync WordPress plugin."
- **admin/Admin.php**: "PHP file for the admin class of the Printify Sync plugin authored by Rob Owen."
- **admin/assets/css/admin-styles.css**: "CSS style file for the admin interface of the Printify Sync plugin, authored by Rob Owen."
- **admin/assets/js/admin-script.js**: "JavaScript file for managing the functionality of the Printify Sync plugin in the admin panel."
- **admin/assets/js/admin.js**: "JavaScript file for handling administrative functions of the Printify Sync plugin."
- **admin/assets/js/products.js**: "JavaScript file for managing product-related functionalities in the Printify Sync plugin."
- **admin/assets/js/shops.js**: "This file contains JavaScript code for the Printify Sync plugin related to shop functionalities."
- **admin/includes/Helper.php**: "Helper class file for the Printify Sync plugin in the admin includes directory."
- **admin/templates/products-section.php**: "PHP template for the products section in the admin area of the Printify Sync plugin."
- **admin/templates/settings-page.php**: "PHP file for the settings page of the Printify Sync plugin."
- **admin/templates/shops-section.php**: "PHP file for the 'shops-section' class in the Printify Sync plugin, authored by Rob Owen."
- **composer.json**: "A JSON file for a WordPress plugin project that syncs Woocommerce and Printify."
- **composer.lock**: "File that locks the dependencies of a project to a known state, generated automatically by Composer."
- **debug.log**: "Log file containing debug information about OpenAI chat completion responses."
- **delete_workflows.sh**: "Shell script to check GitHub CLI authentication status and prompt for login if not authenticated."
- **error.log**: A log file recording errors that occur within a system or software.
- **eslint.config.js**: "Configuration file for ESLint specifying rules for JavaScript files."
- **includes/Api.php**: "This file contains the Api class for the Printify Sync plugin, authored by Rob Owen."
- **includes/Autoloader.php**: "PHP autoloader file for the Printify Sync plugin."
- **includes/Helpers/CategoriesHelper.php**: "This file contains the CategoriesHelper class for the Printify Sync plugin authored by Rob Owen."
- **includes/Helpers/ImagesHelper.php**: "This file contains the ImagesHelper class used in the Printify Sync plugin, authored by Rob Owen."
- **includes/Helpers/TagsHelper.php**: "PHP file for the TagsHelper class used in the Printify Sync plugin."
- **includes/Helpers/VariantsHelper.php**: "This file contains the VariantsHelper class for the Printify Sync plugin, authored by Rob Owen."
- **includes/ImageUpload.php**: "This file contains the ImageUpload class used for the Printify Sync plugin, authored by Rob Owen."
- **includes/Logger.php**: "PHP file containing the Logger class for the Printify Sync plugin, authored by Rob Owen."
- **includes/PrintifyAPI.php**: "PHP file containing the PrintifyAPI class for the Printify Sync plugin, authored by Rob Owen."
- **includes/ProductImport.php**: "PHP file containing the ProductImport class for the Printify Sync plugin."
- **includes/ProductImportCron.php**: "PHP file containing the ProductImportCron class for the Printify Sync plugin."
- **includes/ProductImporter.php**: "PHP file containing the ProductImporter class for the Printify Sync plugin, authored by Rob Owen."
- **package-lock.json**: "A file that contains the exact versions of the npm dependencies for a WooCommerce and Printify synchronization project."
- **package.json**: "Configuration file for a WordPress plugin that syncs WooCommerce and Printify."
- **phpcs.xml.dist**: "Configuration file for PHP_CodeSniffer specifying WordPress coding standards rules."
- **phpstan.neon**: A configuration file for PHPStan, setting the analysis level to maximum and specifying the paths to analyze.
- **phpunit.xml.dist**: "Configuration file for PHPUnit testing in a WordPress plugin."
- **update-readme.js**: "JavaScript file for updating README using Node.js file system, path modules and axios for HTTP requests."
- **wp-woocommerce-printify-sync.php**: "This file contains the WordPress WooCommerce Printify Sync plugin class written by Rob Owen."
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