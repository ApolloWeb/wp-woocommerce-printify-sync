namespace ApolloWeb\WPWooCommercePrintifySync\Repositories;

use ApolloWeb\WPWooCommercePrintifySync\API\ApiClient;

/**
 * Class ProductRepository
 *
 * Repository for managing products.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Repositories
 */
class ProductRepository implements ProductRepositoryInterface
{
    /**
     * @var ApiClient
     */
    protected $apiClient;

    /**
     * ProductRepository constructor.
     *
     * @param ApiClient $apiClient
     */
    public function __construct(ApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    /**
     * Get products from Printify.
     *
     * @return array
     */
    public function getProducts(): array
    {
        return $this->apiClient->getProducts();
    }

    /**
     * Save product data to WooCommerce.
     *
     * @param array $productData
     */
    public function saveProduct(array $productData): void
    {
        // Logic to save product data to WooCommerce
    }
}
