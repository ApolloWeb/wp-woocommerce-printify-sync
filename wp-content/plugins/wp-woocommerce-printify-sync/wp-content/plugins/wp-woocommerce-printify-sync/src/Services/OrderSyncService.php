namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use ApolloWeb\WPWooCommercePrintifySync\Repositories\OrderRepositoryInterface;

/**
 * Class OrderSyncService
 *
 * Service for synchronizing orders.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Services
 */
class OrderSyncService implements OrderSyncServiceInterface
{
    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * OrderSyncService constructor.
     *
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(OrderRepositoryInterface $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    /**
     * Sync orders.
     */
    public function syncOrders(): void
    {
        $orders = $this->orderRepository->getOrders();

        foreach ($orders as $order) {
            $this->orderRepository->saveOrder($order);
        }
    }
}
