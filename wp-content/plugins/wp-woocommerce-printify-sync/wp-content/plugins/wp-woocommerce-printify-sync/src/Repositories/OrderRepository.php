namespace ApolloWeb\WPWooCommercePrintifySync\Repositories;

use ApolloWeb\WPWooCommercePrintifySync\API\ApiClient;

/**
 * Class OrderRepository
 *
 * Repository for managing orders.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Repositories
 */
class OrderRepository implements OrderRepositoryInterface
{
    /**
     * @var ApiClient
     */
    protected $apiClient;

    /**
     * OrderRepository constructor.
     *
     * @param ApiClient $apiClient
     */
    public function __construct(ApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    /**
     * Get orders from Printify.
     *
     * @return array
     */
    public function getOrders(): array
    {
        return $this->apiClient->getOrders();
    }

    /**
     * Save order data to WooCommerce.
     *
     * @param array $orderData
     */
    public function saveOrder(array $orderData): void
    {
        // Logic to save order data to WooCommerce
    }
}
