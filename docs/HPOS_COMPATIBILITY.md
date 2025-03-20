# HPOS Compatibility

This plugin is fully compatible with WooCommerce's High-Performance Order Storage (HPOS) feature.

## What is HPOS?

HPOS (High-Performance Order Storage) is a new order storage system in WooCommerce that moves orders from WordPress post tables to custom database tables. This provides better performance and reliability for stores with many orders.

## Implementation Details

Our plugin implements HPOS compatibility through:

1. Declaration of compatibility in the main plugin file
2. Use of WooCommerce's CRUD methods for order operations
3. Avoiding direct post meta table queries
4. Using `wc_get_order()` and the WooCommerce order object API
5. Using HPOS-aware methods for accessing order notes and metadata

## Testing HPOS Compatibility

You can test HPOS compatibility by enabling the feature in WooCommerce:

1. Go to WooCommerce > Settings > Advanced > Features
2. Enable "High-Performance Order Storage" (you can choose "Read Only" mode first)
3. Test the Printify Sync plugin with this feature enabled

## Troubleshooting

If you encounter any issues with HPOS compatibility:

1. Check the WooCommerce System Status report for any compatibility warnings
2. Make sure you're running the latest version of both WooCommerce and this plugin
3. Clear your cache and try again
4. Contact support with details of any specific errors you encounter
