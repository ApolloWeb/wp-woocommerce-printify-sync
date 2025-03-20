<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Ajax;

use ApolloWeb\WPWooCommercePrintifySync\Core\ServiceContainer;
use ApolloWeb\WPWooCommercePrintifySync\Core\Cache;
use ApolloWeb\WPWooCommercePrintifySync\API\Interfaces\PrintifyAPIInterface;
use ApolloWeb\WPWooCommercePrintifySync\WooCommerce\Interfaces\OrderImporterInterface;
use ApolloWeb\WPWooCommercePrintifySync\WooCommerce\Interfaces\ProductImporterInterface;
use ApolloWeb\WPWooCommercePrintifySync\Ajax\Handlers\ProductHandler;

class AjaxHandler
{
    /**
     * @var ServiceContainer
     */
    protected $container;
    
    /**
     * @var HandlerFactory
     */
    protected $factory;
    
    /**
     * Constructor
     * 
     * @param ServiceContainer $container
     */
    public function __construct(ServiceContainer $container)
    {
        $this->container = $container;
        $this->factory = new HandlerFactory($container);
    }
    
    /**
     * Main entry point for handling AJAX requests
     */
    public function handleAjax()
    {
        // Verify nonce first
        if (!isset($_REQUEST['nonce']) || !wp_verify_nonce($_REQUEST['nonce'], 'wpwps_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }
        
        $action = isset($_REQUEST['action_type']) ? sanitize_text_field($_REQUEST['action_type']) : '';
        
        // Get a handler for this action
        $handler = $this->factory->createHandler($action);
        
        if ($handler !== null) {
            call_user_func($handler);
        } else {
            wp_send_json_error(['message' => 'Unknown action type: ' . $action]);
        }
    }
    
    // The existing methods will remain for backward compatibility during migration.
    // When all code has been moved to handlers, these methods can be removed.
    // Each method implementation is replaced with a call to the new handler.
    
    private function fetchPrintifyProducts()
    {
        call_user_func($this->factory->createHandler('fetch_printify_products'));
    }
    
    private function fetchPrintifyOrders()
    {
        call_user_func($this->factory->createHandler('fetch_printify_orders'));
    }
    
    private function importOrderToWoo()
    {
        call_user_func($this->factory->createHandler('import_order_to_woo'));
    }
    
    private function saveSettings()
    {
        call_user_func($this->factory->createHandler('save_settings'));
    }
    
    private function testConnection()
    {
        call_user_func($this->factory->createHandler('test_connection'));
    }
    
    private function fetchShops()
    {
        call_user_func($this->factory->createHandler('fetch_shops'));
    }
    
    private function selectShop()
    {
        call_user_func($this->factory->createHandler('select_shop'));
    }
    
    private function manualSync()
    {
        call_user_func($this->factory->createHandler('manual_sync'));
    }
    
    private function manualSyncOrders()
    {
        call_user_func($this->factory->createHandler('manual_sync_orders'));
    }
    
    private function importProductToWoo()
    {
        call_user_func($this->factory->createHandler('import_product_to_woo'));
    }
    
    private function bulkImportProducts()
    {
        call_user_func($this->factory->createHandler('bulk_import_products'));
    }
    
    private function bulkImportOrders()
    {
        call_user_func($this->factory->createHandler('bulk_import_orders'));
    }
    
    private function debugDirectOrders()
    {
        call_user_func($this->factory->createHandler('debug_direct_orders'));
    }
    
    private function clearAllData()
    {
        call_user_func($this->factory->createHandler('clear_all_data'));
    }

    private function importAllProducts()
    {
        call_user_func($this->factory->createHandler('import_all_products'));
    }

    private function importAllOrders()
    {
        call_user_func($this->factory->createHandler('import_all_orders'));
    }

    private function getOrderImportProgress()
    {
        call_user_func($this->factory->createHandler('get_order_import_progress'));
    }
}
