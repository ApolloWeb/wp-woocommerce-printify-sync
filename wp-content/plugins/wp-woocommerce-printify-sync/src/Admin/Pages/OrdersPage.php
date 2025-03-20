<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Pages;

use ApolloWeb\WPWooCommercePrintifySync\Core\ServiceContainer;
use ApolloWeb\WPWooCommercePrintifySync\Core\Cache;

class OrdersPage extends AbstractAdminPage
{
    public function __construct($templateEngine, ServiceContainer $container = null)
    {
        parent::__construct($templateEngine, $container);
        $this->slug = 'wpwps-orders';
        $this->pageTitle = 'Printify Orders';
        $this->menuTitle = 'Orders';
    }

    public function render()
    {
        // Do not automatically clear cache on page load
        // Check if cache was manually cleared via action
        $cache_cleared = isset($_GET['cache_cleared']) && $_GET['cache_cleared'] === '1';
        
        return $this->templateEngine->render('admin/wpwps-orders.php', [
            'partials' => ['wpwps-header', 'wpwps-alerts', 'wpwps-filters'],
            'container' => $this->container,
            'cache_cleared' => $cache_cleared
        ]);
    }

    /**
     * Clear the orders cache when explicitly called, not automatically
     */
    private function clearOrdersCache()
    {
        $shopId = get_option('wpwps_printify_shop_id', '');
        
        if (!empty($shopId)) {
            // Log cache clearing attempt
            error_log("Automatically clearing orders cache for shop ID: $shopId on page load");
            
            $deleted = Cache::deleteOrders($shopId);
            
            if ($deleted) {
                error_log("Successfully cleared orders cache for shop ID: $shopId");
            } else {
                error_log("Failed to clear orders cache for shop ID: $shopId or cache was already empty");
            }
        }
    }

    public function getRequiredAssets(): array
    {
        return [
            'styles' => ['wpwps-orders', 'wpwps-common'],
            'scripts' => ['wpwps-orders']
        ];
    }
}
