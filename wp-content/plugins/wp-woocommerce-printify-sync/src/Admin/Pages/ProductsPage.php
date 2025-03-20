<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Pages;

use ApolloWeb\WPWooCommercePrintifySync\Core\ServiceContainer;
use ApolloWeb\WPWooCommercePrintifySync\Core\Cache;

class ProductsPage extends AbstractAdminPage
{
    public function __construct($templateEngine, ServiceContainer $container = null)
    {
        parent::__construct($templateEngine, $container);
        $this->slug = 'wpwps-products';
        $this->pageTitle = 'Printify Products';
        $this->menuTitle = 'Products';
    }

    public function render()
    {
        // Clear the cache when the page loads
        $this->clearProductsCache();
        
        return $this->templateEngine->render('admin/wpwps-products.php', [
            'partials' => ['wpwps-header', 'wpwps-alerts', 'wpwps-filters'],
            'container' => $this->container,
            'cache_cleared' => true // Pass flag to template
        ]);
    }

    /**
     * Clear the products cache on page load
     */
    private function clearProductsCache()
    {
        $shopId = get_option('wpwps_printify_shop_id', '');
        
        if (!empty($shopId)) {
            // Log cache clearing attempt
            error_log("Automatically clearing product cache for shop ID: $shopId on page load");
            
            $deleted = Cache::deleteProducts($shopId);
            
            if ($deleted) {
                error_log("Successfully cleared product cache for shop ID: $shopId");
                // You could set an admin notice here if desired
            } else {
                error_log("Failed to clear product cache for shop ID: $shopId or cache was already empty");
            }
            
            // Also clear orders cache
            Cache::deleteOrders($shopId);
        }
    }

    public function getRequiredAssets(): array
    {
        return [
            'styles' => ['wpwps-products', 'wpwps-common'],
            'scripts' => ['wpwps-products']
        ];
    }
}
