namespace ApolloWeb\WPWooCommercePrintifySync\Repositories;

interface ProductRepositoryInterface
{
    public function getProducts(): array;
    public function saveProduct(array $productData): void;
}
