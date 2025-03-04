<?phpnamespace ApolloWeb\WPWooCommercePrintifySync\API;use ApolloWeb\WPWooCommercePrintifySync\Helpers\ApiRequestHelper;class WooCommerceApi
{
    public function getProducts()
    {
        $args = [
            'status' => 'publish',
            'limit'  => -1
        ];        $products = wc_get_products($args);        return $products;
    }    public function updateProduct($productId, $data)
    {
        $product = wc_get_product($productId);        if (!$product) {
            return new \WP_Error('product_not_found', 'Product not found');
        }        foreach ($data as $key => $value) {
            $product->set_prop($key, $value);
        }        $product->save();
        return $product;
    }    // Add more methods as needed...
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
