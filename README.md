# WP WooCommerce Printify Sync

## Plugin Overview

The `WP WooCommerce Printify Sync` plugin allows WooCommerce store owners to seamlessly integrate and sync products from Printify to their WooCommerce store. It includes functionalities for product import, shipping management, order management, webhook management, testing, API handling, and UI enhancements.

## Features

1. **Product Import**: Import products from Printify to WooCommerce.
2. **Shipping Management**: Calculate shipping rates based on customer location and print provider shipping profiles.
3. **Order Management**: Sync WooCommerce orders with Printify and update order status and tracking information.
4. **Webhook Management**: Create, list, delete, and simulate webhooks from the admin panel.
5. **Testing**: Use Postman for testing API endpoints.
6. **API Handling**: Fetch and cache shipping profiles from the Printify API.
7. **UI Enhancements**: Provide an admin interface to manage settings, shipping profiles, and webhooks.
8. **Logging**: Log important events and errors.
9. **Support Tickets**: Manage support tickets.
10. **Notifications**: Display notifications in the admin panel.

## Installation

1. **Upload** the plugin files to the `/wp-content/plugins/wp-woocommerce-printify-sync` directory, or install the plugin through the WordPress plugins screen directly.
2. **Activate** the plugin through the 'Plugins' screen in WordPress.

## Usage

### Settings

1. **API Key and Endpoint**: Navigate to the plugin settings and enter your Printify API key and endpoint.

### Product Import

1. **Import Products**: Navigate to the plugin settings and use the import functionality to sync products from Printify to WooCommerce.
2. **Update Products**: Ensure the product details, prices, and stock levels are up-to-date by running the update functionality.

### Shipping Management

1. **Calculate Shipping Rates**: The plugin calculates shipping rates based on customer location and print provider shipping profiles.
2. **Display Shipping Methods**: The available shipping methods are displayed during the checkout process.
3. **Store Shipping Details**: The selected shipping method and cost are stored in the order metadata.
4. **Display Tracking Number**: The tracking number is displayed in order details and emails.

### Order Management

1. **Sync Orders**: Sync WooCommerce orders with Printify to ensure accurate order fulfillment.
2. **Update Order Status**: Update the status and tracking information of orders as they are processed.

### Webhook Management

1. **Create Webhooks**: Create webhooks from the admin panel to receive notifications from Printify.
2. **List Webhooks**: List all created webhooks in the admin panel.
3. **Delete Webhooks**: Delete webhooks that are no longer needed.
4. **Simulate Webhooks**: Simulate webhook events for testing purposes.
5. **Capture Responses**: Capture and store webhook responses for verification.

### Testing

1. **Postman Integration**: Use Postman for testing API endpoints.
   - Import the provided Postman collection file (`WP_WooCommerce_Printify_Sync.postman_collection.json`) located in the `postman/` directory.
   - Set the `base_url` and `PRINTIFY_API_KEY` variables in Postman to match your setup.
2. **Import Live Data**: Import live data to Postman for testing.
3. **Refresh Data**: Refresh data in the admin panel to ensure accuracy.

### API Handling

1. **Fetch Shipping Profiles**: Fetch and cache shipping profiles from the Printify API.
2. **Handle API Requests**: Handle API requests and responses to ensure accurate data synchronization.
3. **Calculate Shipping Rates**: Calculate shipping rates based on profiles and customer location.

### UI Enhancements

1. **Admin Interface**: Provide an admin interface to manage settings, shipping profiles, and webhooks.
2. **Animations and Dynamics**: Add animations and dynamic elements for a better user experience.

### Logging

1. **Log Events**: Use the `WPWCSLogger` class to log important events and errors.
   - Example: `$logger = new WPWCSLogger(); $logger->info('This is an info message.');`

### Support Tickets

1. **Manage Tickets**: Use the `Ticketing` class to create and manage support tickets.
   - Example: `$ticketing = new Ticketing(); $ticket_id = $ticketing->create_ticket('Sample Issue', 'Description of the issue', 'high');`

### Notifications

1. **Display Notifications**: Use the `WPWCSNotifications` class to display notifications in the admin panel.
   - Example: `$notifications = new WPWCSNotifications(); $notifications->add_notification('This is a notification.', 'success');`

## License

This plugin is licensed under the MIT License.