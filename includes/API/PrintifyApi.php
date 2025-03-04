<?phpnamespace ApolloWeb\WPWooCommercePrintifySync\API;use ApolloWeb\WPWooCommercePrintifySync\Helpers\ApiRequestHelper;class PrintifyApi
{
    private $apiKey;
    private $apiUrl = 'https://api.printify.com/v1/';    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }    private function getHeaders()
    {
        return [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type'  => 'application/json'
        ];
    }    public function getShops()
    {
        $url = $this->apiUrl . 'shops.json';
        return ApiRequestHelper::getRequest($url, $this->getHeaders());
    }    public function getProducts($shopId)
    {
        $url = $this->apiUrl . 'shops/' . $shopId . '/products.json';
        return ApiRequestHelper::getRequest($url, $this->getHeaders());
    }    public function getProduct($shopId, $productId)
    {
        $url = $this->apiUrl . 'shops/' . $shopId . '/products/' . $productId . '.json';
        return ApiRequestHelper::getRequest($url, $this->getHeaders());
    }    public function createProduct($shopId, $productData)
    {
        $url = $this->apiUrl . 'shops/' . $shopId . '/products.json';
        return ApiRequestHelper::postRequest($url, $this->getHeaders(), $productData);
    }    public function updateProduct($shopId, $productId, $productData)
    {
        $url = $this->apiUrl . 'shops/' . $shopId . '/products/' . $productId . '.json';
        return ApiRequestHelper::putRequest($url, $this->getHeaders(), $productData);
    }    public function deleteProduct($shopId, $productId)
    {
        $url = $this->apiUrl . 'shops/' . $shopId . '/products/' . $productId . '.json';
        return ApiRequestHelper::deleteRequest($url, $this->getHeaders());
    }    // Other methods to interact with Printify API endpoints...
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
