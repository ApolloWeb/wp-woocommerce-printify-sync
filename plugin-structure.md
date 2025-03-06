# Plugin Structure

## Plugin Meta
```php name=wp-woocommerce-printify-sync.php
/**
 * Plugin Name: WP WooCommerce Printify Sync
 * Description: Sync products from Printify to WooCommerce
 * Plugin URI: https://github.com/ApolloWeb/wp-woocommerce-printify-sync
 * Version: 1.1.1
 * Author: ApolloWeb
 * Author URI: https://github.com/ApolloWeb
 * Text Domain: wp-woocommerce-printify-sync
 * Domain Path: /languages
 * Requires at least: 5.6
 * Requires PHP: 7.3
 * License: MIT
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Define plugin constants
define( 'WPWPPS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPWPPS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Include necessary files
require_once WPWPPS_PLUGIN_DIR . 'includes/class-admin-dashboard.php';
require_once WPWPPS_PLUGIN_DIR . 'includes/class-admin-settings.php';
require_once WPWPPS_PLUGIN_DIR . 'includes/class-admin-logs.php';
require_once WPWPPS_PLUGIN_DIR . 'includes/class-printify-api.php';
require_once WPWPPS_PLUGIN_DIR . 'includes/class-woocommerce-api.php';
require_once WPWPPS_PLUGIN_DIR . 'includes/class-currency-exchange-cron.php';
require_once WPWPPS_PLUGIN_DIR . 'includes/class-stock-sync-cron.php';
require_once WPWPPS_PLUGIN_DIR . 'includes/class-encryption-helper.php';
require_once WPWPPS_PLUGIN_DIR . 'includes/class-image-optimization-helper.php';
require_once WPWPPS_PLUGIN_DIR . 'includes/class-product-model.php';
require_once WPWPPS_PLUGIN_DIR . 'includes/class-order-model.php';
require_once WPWPPS_PLUGIN_DIR . 'includes/class-product-sync-service.php';
require_once WPWPPS_PLUGIN_DIR . 'includes/class-order-processing-service.php';
require_once WPWPPS_PLUGIN_DIR . 'includes/class-order-webhook-handler.php';
require_once WPWPPS_PLUGIN_DIR . 'includes/class-refund-webhook-handler.php';

// Enqueue scripts and styles
function wpwpps_enqueue_scripts() {
    wp_enqueue_style( 'admin-dashboard', WPWPPS_PLUGIN_URL . 'src/css/admin-dashboard.css' );
    wp_enqueue_style( 'product-sync', WPWPPS_PLUGIN_URL . 'src/css/product-sync.css' );
    wp_enqueue_script( 'admin-dashboard', WPWPPS_PLUGIN_URL . 'src/js/admin-dashboard.js', array( 'jquery' ), false, true );
    wp_enqueue_script( 'product-sync', WPWPPS_PLUGIN_URL . 'src/js/product-sync.js', array( 'jquery' ), false, true );
}
add_action( 'admin_enqueue_scripts', 'wpwpps_enqueue_scripts' );
```

## Directory Structure

- **includes/**: Contains all PHP source files, organized by type.
  - **Admin/**: Admin-specific functionalities.
    - **class-admin-dashboard.php**
    - **class-admin-settings.php**
    - **class-admin-logs.php**
  - **API/**: API integrations and handlers.
    - **class-printify-api.php**
    - **class-woocommerce-api.php**
  - **Cron/**: Cron job functionalities.
    - **class-currency-exchange-cron.php**
    - **class-stock-sync-cron.php**
  - **Helpers/**: Helper classes and utility functions.
    - **class-encryption-helper.php**
    - **class-image-optimization-helper.php**
  - **Models/**: Data models.
    - **class-product-model.php**
    - **class-order-model.php**
  - **Services/**: Core service classes.
    - **class-product-sync-service.php**
    - **class-order-processing-service.php**
  - **Webhooks/**: Webhook handlers.
    - **class-order-webhook-handler.php**
    - **class-refund-webhook-handler.php**

- **src/**: Contains JS and SASS files, organized by type.
  - **css/**
    - **admin-dashboard.scss**
    - **product-sync.scss**
  - **js/**
    - **admin-dashboard.js**
    - **product-sync.js**
  - **sass/**: Contains the compiled CSS files.
    - **admin-dashboard.css**
    - **product-sync.css**
  - **index.js**: Main JS file to bundle and export necessary JS functionalities.

- **assets/**: Contains images.
  - **images/**

- **languages/**: Contains translation files.

- **templates/**: Contains template files for the plugin.

- **tests/**: Contains unit tests and integration tests.
  - **unit/**
  - **integration/**

## Example Class Implementations

### includes/Admin/class-admin-dashboard.php
```php name=includes/Admin/class-admin-dashboard.php
namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

class AdminDashboard {
    public function __construct() {
        // Initialization code
    }

    public function renderDashboard() {
        // Render the admin dashboard
    }
}
```

### includes/API/class-printify-api.php
```php name=includes/API/class-printify-api.php
namespace ApolloWeb\WPWooCommercePrintifySync\API;

class PrintifyAPI {
    private $apiKey;

    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
    }

    public function getProducts() {
        // Code to fetch products from Printify
    }

    public function createOrder($orderData) {
        // Code to create an order in Printify
    }
}
```

### includes/Services/class-product-sync-service.php
```php name=includes/Services/class-product-sync-service.php
namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use ApolloWeb\WPWooCommercePrintifySync\API\PrintifyAPI;
use ApolloWeb\WPWooCommercePrintifySync\API\WooCommerceAPI;

class ProductSyncService {
    private $printifyAPI;
    private $wooCommerceAPI;

    public function __construct(PrintifyAPI $printifyAPI, WooCommerceAPI $wooCommerceAPI) {
        $this->printifyAPI = $printifyAPI;
        $this->wooCommerceAPI = $wooCommerceAPI;
    }

    public function syncProducts() {
        // Code to synchronize products between Printify and WooCommerce
    }
}
```

## Best Practices

1. **Single Responsibility Principle (SRP)**: Each class should have one and only one reason to change, focusing on a single piece of functionality.
2. **Namespace and Class Names**: Use PascalCase for class names and camelCase for functions to maintain consistency.
3. **File and Folder Structure**: Organize classes into folders by type to enhance readability and maintainability.
4. **Helper and Abstract Classes**: Use helper classes and abstract classes where appropriate to avoid code duplication and promote reusability.
5. **Assets Management**: Load assets using `plugin_dir_path` and ensure they are split into smaller files for specific pages to reduce load times and improve maintainability.
6. **Security and Performance**: Ensure secure storage of API keys, use AJAX to prevent CORS issues, optimize images, and implement caching where necessary.
7. **Testing**: Include unit and integration tests to ensure code quality and reliability.

## Conclusion

This structure ensures that the plugin is organized, maintainable, and scalable, adhering to SOLID principles and best practices for WordPress development.