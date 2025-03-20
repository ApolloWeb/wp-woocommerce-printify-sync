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
        // Clear orders cache to ensure fresh data on page load
        $this->clearOrdersCache();
        
        return $this->templateEngine->render('admin/wpwps-orders.php', [
            'partials' => ['wpwps-header', 'wpwps-alerts'],
            'container' => $this->container,
            'cache_cleared' => true // Pass flag to template
        ]);
    }
    
    /**
     * Clear the orders cache on page load
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
