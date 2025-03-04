<?phpnamespace ApolloWeb\WPWooCommercePrintifySync\Sync;use ApolloWeb\WPWooCommercePrintifySync\API\PrintifyApi;class ProductSync
{
    public static function register()
    {
        add_action('init', [__CLASS__, 'syncProducts']);
    }    public static function syncProducts()
    {
        $apiKey = get_option('printify_api_key');
        $defaultShopId = get_option('default_shop');        if (!$apiKey || !$defaultShopId) {
            return;
        }        $printifyApi = new PrintifyApi($apiKey);
        $products = $printifyApi->getProducts($defaultShopId);        if (is_wp_error($products)) {
            // Handle error
            return;
        }        // Process products
        foreach ($products['data'] as $product) {
            // Sync product with WooCommerce
        }
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
