# WP WooCommerce Printify Sync

![Version](https://img.shields.io/badge/version-1.0.0-blue)
![PHP](https://img.shields.io/badge/PHP-7.3%2B-purple)
![WordPress](https://img.shields.io/badge/WordPress-5.6%2B-green)
![License](https://img.shields.io/badge/license-MIT-orange)

A comprehensive WordPress plugin that seamlessly integrates Printify with WooCommerce, enabling automated product syncing, order management, and advanced features for print-on-demand businesses.

## 🚀 Features

- **Complete Printify Integration**: Seamlessly sync products, images, and orders between Printify and WooCommerce
- **HPOS Compatibility**: Optimized for WooCommerce High-Performance Order Storage
- **Advanced Queue System**: Reliable processing using WooCommerce Action Scheduler
- **Exchange Rate Management**: Dynamic currency conversion using FreeCurrencyAPI
- **Real-time Notifications**: Webhooks integration for instant order status updates
- **AI-Powered Support**: Integrated ticketing system with AI assistance
- **Robust Logging**: Comprehensive activity tracking for easy debugging
- **Secure API Communication**: Encrypted API key storage and secure communication protocols
- **User-Friendly Admin Interface**: Built with AdminLTE for an intuitive backend experience

## 📋 Requirements

- WordPress 5.6+
- PHP 7.3+
- WooCommerce 5.0+
- SSL Certificate

## 🔧 Installation

### Manual Installation

1. Download the plugin zip file
2. Navigate to your WordPress dashboard
3. Go to Plugins > Add New > Upload Plugin
4. Upload the zip file and click "Install Now"
5. Activate the plugin through the 'Plugins' menu in WordPress

### Via Composer

```bash
composer require apolloweb/wp-woocommerce-printify-sync
```

## ⚙️ Configuration

1. Go to WooCommerce > Settings > Printify Sync
2. Enter your Printify API key
3. Configure sync settings:
   - Product sync frequency
   - Order processing preferences
   - Shipping profile mappings
   - Notification preferences

## 📦 Usage

### Product Synchronization

Products can be synchronized from Printify in three ways:

1. **Automatic Sync**: Products are automatically synced based on your configured schedule
2. **Manual Import**: Use the "Import Products" button from the admin dashboard
3. **Individual Products**: Click "Import from Printify" when creating a new product

### Order Management

Orders placed in your WooCommerce store are:

1. Automatically sent to Printify for processing
2. Status updates from Printify are reflected in WooCommerce
3. Shipping notifications are sent to customers
4. Tracking information is added to orders when available

## 🏗️ Architecture

This plugin follows SOLID principles and uses an OOP approach with PSR-12 coding standards:

```
wp-woocommerce-printify-sync/
├── assets/
│   ├── css/
│   └── js/
│       ├── modules/
│       │   ├── core.js
│       │   ├── notification.js
│       │   ├── sync.js
│       │   └── ui.js
│       └── admin.js
├── includes/
│   ├── Abstracts/
│   ├── Admin/
│   ├── Api/
│   ├── Interfaces/
│   ├── Queue/
│   ├── Services/
│   ├── Utils/
│   └── View/
└── views/
```

## 🔌 Hooks and Filters

The plugin provides extensive hooks and filters for customization:

### Actions

```php
do_action('apolloweb_printify_before_product_sync', $product_id);
do_action('apolloweb_printify_after_product_sync', $product_id, $result);
do_action('apolloweb_printify_before_order_submit', $order_id);
do_action('apolloweb_printify_after_order_submit', $order_id, $printify_order_id);
```

### Filters

```php
$product_data = apply_filters('apolloweb_printify_product_data', $product_data, $product_id);
$order_data = apply_filters('apolloweb_printify_order_data', $order_data, $order_id);
$shipping_methods = apply_filters('apolloweb_printify_shipping_methods', $shipping_methods);
```

## 🧪 Testing

Run the test suite with:

```bash
composer test
```

## 🔄 Development Workflow

1. Clone the repository
2. Install dependencies:
   ```bash
   composer install
   npm install
   ```
3. Start the development environment:
   ```bash
   docker-compose up -d
   ```
4. Access the development site at `http://localhost:8080`

## 📖 Documentation

For full documentation, please visit our [GitHub Wiki](https://github.com/ApolloWeb/wp-woocommerce-printify-sync/wiki).

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create a feature branch: `git checkout -b feature-name`
3. Commit your changes: `git commit -m 'Add some feature'`
4. Push to the branch: `git push origin feature-name`
5. Open a Pull Request

## 📜 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgements

- [WooCommerce](https://woocommerce.com/)
- [Printify API](https://developers.printify.com/)
- [AdminLTE](https://adminlte.io/)
- [Botiga Theme](https://athemes.com/theme/botiga/)

## 📬 Contact

- Email: [hello@apollo-web.co.uk](mailto:hello@apollo-web.co.uk)
- Slack: [ApolloWeb Workspace](https://apollowebworkspace.slack.com/archives/C08FLP5Q8FL)
- GitHub: [ApolloWeb](https://github.com/ApolloWeb)

For support or inquiries, please open an issue on our [GitHub repository](https://github.com/ApolloWeb/wp-woocommerce-printify-sync/issues).