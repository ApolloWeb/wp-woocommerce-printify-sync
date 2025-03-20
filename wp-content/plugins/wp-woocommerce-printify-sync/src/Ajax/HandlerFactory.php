<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Ajax;

use ApolloWeb\WPWooCommercePrintifySync\Ajax\Handler\AbstractAjaxHandler;
use ApolloWeb\WPWooCommercePrintifySync\Ajax\Handler\ProductHandler;
use ApolloWeb\WPWooCommercePrintifySync\Ajax\Handler\OrderHandler;
use ApolloWeb\WPWooCommercePrintifySync\Ajax\Handler\SettingsHandler;
use ApolloWeb\WPWooCommercePrintifySync\Ajax\Handler\ProgressTrackerHandler;
use ApolloWeb\WPWooCommercePrintifySync\Core\ServiceContainer;

class HandlerFactory
{
    /**
     * @var ServiceContainer
     */
    private $container;
    
    /**
     * @var array
     */
    private $handlers = [];
    
    /**
     * Constructor
     * 
     * @param ServiceContainer $container
     */
    public function __construct(ServiceContainer $container)
    {
        $this->container = $container;
        
        // Initialize handlers
        $this->initializeHandlers();
    }
    
    /**
     * Initialize handler mappings
     */
    private function initializeHandlers(): void
    {
        // Product handlers
        $this->handlers['fetch_printify_products'] = [ProductHandler::class, 'handle'];
        $this->handlers['import_product_to_woo'] = [ProductHandler::class, 'importProduct'];
        $this->handlers['bulk_import_products'] = [ProductHandler::class, 'bulkImportProducts'];
        $this->handlers['import_all_products'] = [ProductHandler::class, 'importAllProducts'];
        $this->handlers['manual_sync'] = [ProductHandler::class, 'manualSync'];
        
        // Order handlers
        $this->handlers['fetch_printify_orders'] = [OrderHandler::class, 'handle'];
        $this->handlers['import_order_to_woo'] = [OrderHandler::class, 'importOrder'];
        $this->handlers['bulk_import_orders'] = [OrderHandler::class, 'bulkImportOrders'];
        $this->handlers['import_all_orders'] = [OrderHandler::class, 'importAllOrders'];
        $this->handlers['get_order_import_progress'] = [OrderHandler::class, 'getOrderImportProgress'];
        $this->handlers['manual_sync_orders'] = [OrderHandler::class, 'manualSyncOrders'];
        
        // Settings handlers
        $this->handlers['save_settings'] = [SettingsHandler::class, 'handle'];
        $this->handlers['test_connection'] = [SettingsHandler::class, 'testConnection'];
        $this->handlers['fetch_shops'] = [SettingsHandler::class, 'fetchShops'];
        $this->handlers['select_shop'] = [SettingsHandler::class, 'selectShop'];
        
        // Progress tracker handlers
        $this->handlers['get_import_progress'] = [ProgressTrackerHandler::class, 'getImportProgress'];
        $this->handlers['get_image_import_progress'] = [ProgressTrackerHandler::class, 'getImageImportProgress'];
        
        // Debug handlers - explicitly list the clear_all_data handler
        $this->handlers['debug_direct_orders'] = [DebugHandler::class, 'directOrdersRequest'];
        $this->handlers['clear_all_data'] = [DebugHandler::class, 'clearAllData'];
        
        // Conditionally add test_ajax handler in debug mode
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->handlers['test_ajax'] = [DebugHandler::class, 'testAjax'];
        }
    }
    
    /**
     * Create a handler for the given action
     * 
     * @param string $action The action type
     * @return callable|null A callable handler or null if not found
     */
    public function createHandler(string $action): ?callable
    {
        if (!isset($this->handlers[$action])) {
            return null;
        }
        
        list($class, $method) = $this->handlers[$action];
        
        // Create handler instance
        $handler = new $class($this->container);
        
        return [$handler, $method];
    }
}
