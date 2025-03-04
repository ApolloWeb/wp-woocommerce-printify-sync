<?phpnamespace ApolloWeb\WPWooCommercePrintifySync\Webhook;use ApolloWeb\WPWooCommercePrintifySync\Sync\ProductSync;
use ApolloWeb\WPWooCommercePrintifySync\Logs\Logger;class WebhookHandler
{
    public static function register()
    {
        add_action('woocommerce_api_printify_webhook', [__CLASS__, 'handleWebhook']);
    }    public static function handleWebhook()
    {
        $payload = file_get_contents('php://input');
        $event = json_decode($payload, true);        if (json_last_error() !== JSON_ERROR_NONE) {
            Logger::log('Invalid JSON payload');
            status_header(400);
            exit;
        }        switch ($event['event']) {
            case 'product.updated':
                ProductSync::syncProducts();
                break;
            case 'order.created':
                // Handle order created event
                break;
            // Add more cases as needed...
        }        status_header(200);
        exit;
    }
} Modified by: Rob Owen On: 2025-03-04 06:00:38 Commit Hash 16c804f Modified by: Rob Owen On: 2025-03-04 06:03:34 Commit Hash 16c804f# Commit Hash 16c804f# Initial commit tracked# -------- End Update Summary --------# Commit Hash 16c804f# Initial commit tracked# -------- End Update Summary --------# Commit Hash 16c804f# Initial commit tracked# -------- End Update Summary --------

#
# -------- Update Summary --------
#
# Modified by: Rob Owen
#
# On: 2025-03-04 08:00:31
#
# Change: Added: } Modified by: Rob Owen On: 2025-03-04 06:00:38 Commit Hash 16c804f Modified by: Rob Owen On: 2025-03-04 06:03:34 Commit Hash 16c804f# Commit Hash 16c804f# Initial commit tracked# -------- End Update Summary --------# Commit Hash 16c804f# Initial commit tracked# -------- End Update Summary --------# Commit Hash 16c804f# Initial commit tracked# -------- End Update Summary --------
#
#
# Commit Hash 16c804f
#
