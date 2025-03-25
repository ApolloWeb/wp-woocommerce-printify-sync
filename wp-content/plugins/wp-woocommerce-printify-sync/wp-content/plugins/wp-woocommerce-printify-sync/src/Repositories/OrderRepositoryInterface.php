namespace ApolloWeb\WPWooCommercePrintifySync\Repositories;

interface OrderRepositoryInterface
{
    public function getOrders(): array;
    public function saveOrder(array $orderData): void;
}
