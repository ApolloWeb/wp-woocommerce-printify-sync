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
<!-- FILE-STRUCTURE-END -->

## File Descriptions
<!-- FILE-DESCRIPTIONS-START -->
- **.php-cs-fixer.php**: "This file configures the PHP CS Fixer tool, allowing risky rules and setting specific rules for code formatting."
- **.phpcs.xml**: "Configuration file for PHP_CodeSniffer specifying rules for WordPress coding standards."
- **.vscode**: (No description available)
- **LICENSE**: Contains the legal terms and conditions for using and distributing the project.
- **admin**: (No description available)
- **composer.json**: "A JSON file for a WordPress plugin project that syncs Woocommerce and Printify."
- **composer.lock**: "File that locks the dependencies of a project to a known state, generated automatically by Composer."
- **debug.log**: "Log file containing debug information about OpenAI chat completion responses."
- **delete_workflows.sh**: "Shell script to check GitHub CLI authentication status and prompt for login if not authenticated."
- **error.log**: A log file recording errors that occur within a system or software.
- **eslint.config.js**: "Configuration file for ESLint specifying rules for JavaScript files."
- **includes**: (No description available)
- **package-lock.json**: "A file that contains the exact versions of the npm dependencies for a WooCommerce and Printify synchronization project."
- **package.json**: "Configuration file for a WordPress plugin that syncs WooCommerce and Printify."
- **phpcs.xml.dist**: "Configuration file for PHP_CodeSniffer specifying WordPress coding standards rules."
- **phpstan.neon**: A configuration file for PHPStan, setting the analysis level to maximum and specifying the paths to analyze.
- **phpunit.xml.dist**: "Configuration file for PHPUnit testing in a WordPress plugin."
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