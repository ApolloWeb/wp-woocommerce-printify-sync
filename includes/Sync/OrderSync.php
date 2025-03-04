<?phpnamespace ApolloWeb\WPWooCommercePrintifySync\Sync;use ApolloWeb\WPWooCommercePrintifySync\API\PrintifyApi;
use ApolloWeb\WPWooCommercePrintifySync\API\WooCommerceApi;
use ApolloWeb\WPWooCommercePrintifySync\Logs\Logger;class OrderSync
{
    private $printifyApi;
    private $wooCommerceApi;    public function __construct()
    {
        $this->printifyApi = new PrintifyApi(get_option('printify_api_key'));
        $this->wooCommerceApi = new WooCommerceApi();
    }    public static function register()
    {
        add_action('woocommerce_order_status_completed', [__CLASS__, 'syncOrder']);
    }    public static function syncOrder($orderId)
    {
        $instance = new self();
        $order = wc_get_order($orderId);        if (!$order) {
            Logger::log('Order not found: ' . $orderId);
            return;
        }        $orderData = $instance->prepareOrderData($order);
        $response = $instance->printifyApi->createOrder($orderData);        if (is_wp_error($response)) {
            Logger::log('Failed to sync order ' . $orderId . ': ' . $response->get_error_message());
        } else {
            Logger::log('Order ' . $orderId . ' synced successfully');
        }
    }    private function prepareOrderData($order)
    {
        // Prepare order data for Printify API
        return [
            // Map WooCommerce order data to Printify order format
        ];
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
