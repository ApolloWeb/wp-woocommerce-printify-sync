### Managing Product Variants

Product variants (sizes, colors, etc.) are synchronized automatically. When a product with variants is imported from Printify, the plugin creates WooCommerce variable products with the appropriate attributes and variations.

To manage product variants:

1. Edit a product in WooCommerce that has been imported from Printify
2. Go to the "Variations" tab
3. You can modify pricing, inventory, and other settings for individual variations
4. If you need to resync variants from Printify, use the "Sync from Printify" button in the Printify Product Data metabox

**Note:** Modifying variant attributes (like adding new colors) should be done in Printify first, then synced to WooCommerce to avoid conflicts.

## Managing Orders

### Sending Orders to Printify

When a customer places an order containing Printify products, it can be sent to Printify for fulfillment:

**Automatic Order Sending:**

If you've enabled "Auto Send Orders" in the settings, orders will be automatically sent to Printify when they reach the configured order status.

**Manual Order Sending:**

1. Go to WooCommerce > Orders
2. Open the order you want to send to Printify
3. In the "Printify Order" metabox, click "Send to Printify"
4. The order will be sent to Printify and you'll see the status update

### Order Status Synchronization

Order statuses are synchronized between WooCommerce and Printify:

- When an order is sent to Printify, its status in Printify is displayed in the WooCommerce order page
- When the status in Printify changes (e.g., from "pending" to "in production"), the WooCommerce status is updated accordingly (if enabled in settings)
- Status updates are received via webhooks from Printify

You can view the Printify order status in:

1. WooCommerce > Orders > Edit Order
2. The "Printify Order" metabox on the right side

### Tracking Information

When Printify adds tracking information to an order, it's automatically synced to WooCommerce:

1. Go to WooCommerce > Orders > Edit Order
2. The "Printify Order" metabox will display the tracking number and carrier
3. A tracking link will be available if provided by Printify
4. If WooCommerce Shipment Tracking is installed, tracking information will be added there as well

Customers will receive tracking information in their order status emails (if enabled in WooCommerce settings).

## Webhooks

### Configuring Printify Webhooks

Webhooks allow Printify to send real-time updates to your WooCommerce store. The plugin automatically sets up the required webhooks when you connect your Printify account.

To verify webhook configuration:

1. Go to Printify Sync > Settings > Webhooks
2. You should see "Webhooks Status: Active" if everything is configured correctly
3. If you see an error, click "Reconnect Webhooks" to attempt automatic configuration

If automatic configuration fails, you can set up webhooks manually in your Printify dashboard:

1. Log in to Printify
2. Go to Settings > API > Webhooks
3. Add a new webhook with the URL displayed in the plugin settings
4. Enable all relevant event types
5. Save the webhook configuration

### Webhook Events

The plugin supports the following webhook events from Printify:

- **Product Published**: When a new product is published in Printify
- **Product Updated**: When a product is updated in Printify
- **Order Status Updated**: When an order status changes in Printify
- **Shipping Details Updated**: When tracking information is added to an order
- **Catalog Update**: When the Printify catalog is updated

You can view webhook activity in the logs section to help with troubleshooting.

## Logs and Troubleshooting

### Viewing Logs

The plugin maintains detailed logs of all synchronization activities, which help with troubleshooting:

1. Go to Printify Sync > Logs
2. Use the filters to narrow down logs by:
   - Log level (debug, info, warning, error)
   - Date range
   - Search term
3. Click "Filter Logs" to apply filters
4. Click "Export to CSV" to download logs for further analysis

Log entries contain:
- Timestamp
- Log level
- Message
- Context (additional data related to the event)

### Common Issues

**Products not importing:**
- Check Printify API key permissions
- Verify product visibility settings in Printify
- Look for errors in the logs with level "error"

**Orders not sending to Printify:**
- Check if the order contains Printify products
- Verify the order status meets the trigger condition
- Check for API connectivity issues in the logs

**Price discrepancies:**
- Verify currency conversion settings
- Check if product prices have been manually edited in WooCommerce
- Ensure the Printify prices are correctly set

**Webhooks not working:**
- Verify your site is accessible from the internet
- Check SSL certificate is valid
- Review webhook configuration in Printify dashboard
- Look for webhook delivery errors in the logs

### Support

If you encounter issues that you cannot resolve:

1. Export logs related to the issue
2. Go to Printify Sync > Settings > Support
3. Fill out the support form with:
   - A clear description of the issue
   - Steps to reproduce
   - Attach the exported logs
4. Click "Send Support Request"

You can also access support through:
- Plugin documentation: https://apolloweb.example.com/docs/printify-sync/
- Email support: support@apolloweb.example.com
- Support forum: https://apolloweb.example.com/support/