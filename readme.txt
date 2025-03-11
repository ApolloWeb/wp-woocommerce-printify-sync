# WP WooCommerce Printify Sync

Plugin Version      - 1.0.1
PHP Version         - 8.2
WordPress Version   - 6.0
WooCommerce Version - 8.0
license             - MIT

A comprehensive WordPress plugin that seamlessly integrates Printify with WooCommerce, enabling automated product syncing, order management, and advanced features for print-on-demand businesses.

## 🚀 Features

- **Complete Printify Integration**: Sync products, images, and orders between Printify and WooCommerce
- **HPOS Compatibility**: Optimized for WooCommerce High-Performance Order Storage
- **Advanced Queue System**: Reliable processing using WooCommerce Action Scheduler
- **Exchange Rate Management**: Dynamic currency conversion using FreeCurrencyAPI
- **Real-time Notifications**: Webhooks integration for instant order status updates
- **Product & Order Webhook Updates**: Automatically update product and order details via webhooks
- **AI-Powered Support**: Integrated ticketing system with AI assistance
- **POP3 Email Polling**: Fetch customer support emails and convert them into tickets
- **SMTP Queuing & Sending**: Scheduled email sending with retry logic
- **Blade Templating Engine**: Clean UI rendering for email and admin panels
- **WooCommerce Email Integration**: Ticketing system integrates with WooCommerce email templates
- **Robust Logging**: Comprehensive activity tracking for debugging
- **Secure API Communication**: Encrypted API key storage and secure communication protocols
- **User-Friendly Admin Interface**: Built with AdminLTE for an intuitive backend experience
- **Geolocation API**: Integrated with [ipgeolocation.io](https://ipgeolocation.io/)

## 📋 Requirements

- WordPress 6.0+
- PHP 8.2+
- WooCommerce 8.0+
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
composer require robowen1972/wp-woocommerce-printify-sync
```

## ⚙️ Configuration

1. Go to WooCommerce > Settings > Printify Sync
2. Enter your Printify API key
3. Enter your Geolocation API key https://ipgeolocation.io/
4. Enter your Exchange Rate API key https://freecurrencyapi.com/
5. Configure webhook settings for product and order updates
6. Configure email settings:
   - POP3 polling credentials
   - SMTP queue settings
   - Blade email templates
   - WooCommerce email template integration
7. Configure sync settings:
   - Product sync frequency
   - Order processing preferences
   - Shipping profile mappings
   - Notification preferences

## 📦 Usage

### Product Synchronization

**Printify -> Woocommerce**: Product details update in real time via webhooks
**Woocommerce -> Printify**: Product details are pushed to Printify via the API triggered by Woocommerce hooks
**Manual Import**: Required at plugin activation.

### Order Management

**Printify -> Woocommerce**: Order details update in real time via webhooks
**Woocommerce -> Printify**: Order details are pushed to Printify via the API triggered by Woocommerce hooks
**Status updates**: Order statuses from Printify are reflected in WooCommerce
**Tracking Information**: Tracking information is sent to customer and added to orders

### Ticketing System

**Email to Ticket**: Incoming customer emails (via POP3) are converted into tickets
**Reply via SMTP**: Responses are sent from WooCommerce using queued SMTP messages
**WooCommerce Email Integration**: Ticket responses follow WooCommerce email templates
**Admin Interface**: View and manage tickets in WooCommerce

## 🏗️ Deployment & Development

### Docker Deployment
To deploy the plugin using Docker, use the following command:
```bash
docker-compose up -d --build
```

### Running Tests
Run the test suite with:
```bash
composer test
```

### GitHub Actions & Postman Mock Servers
- Automated testing is configured via **GitHub Actions**
- API responses are validated using **Postman Mock Servers**

## 📖 Documentation
For full documentation, please visit our GitHub Wiki - https://github.com/robowen1972/wp-woocommerce-printify-sync/wiki.

## 🤝 Contributing
Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create a feature branch: `git checkout -b feature-name`
3. Commit your changes: `git commit -m 'Add some feature'`
4. Push to the branch: `git push origin feature-name`
5. Open a Pull Request

## 📜 License
This project is licensed under the MIT License - see the LICENSE file for details.

## 📬 Contact

- Email: hello@apollo-web.co.uk
- GitHub: https://github.com/robowen1972
- Slack: https://apollowebworkspace.slack.com/archives/C08FLP5Q8FL

For support or inquiries, please open an issue on our https://github.com/robowen1972/wp-woocommerce-printify-sync/issues.
