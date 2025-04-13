<?php
/**
 * API Controller for WP WooCommerce Printify Sync
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\API
 */

namespace ApolloWeb\WPWooCommercePrintifySync\API;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Class APIController
 * Handles all REST API endpoints for the plugin
 */
class APIController {
    /**
     * API namespace
     *
     * @var string
     */
    private $namespace = 'wpwps/v1';
    
    /**
     * Printify API client
     *
     * @var PrintifyAPIClient
     */
    private $printifyClient;
    
    /**
     * WooCommerce API client
     *
     * @var WooCommerceAPIClient
     */
    private $wooClient;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->printifyClient = new PrintifyAPIClient();
        $this->wooClient = new WooCommerceAPIClient();
    }
    
    /**
     * Register REST API routes
     *
     * @return void
     */
    public function registerRoutes(): void {
        // Products endpoints
        register_rest_route($this->namespace, '/products', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'getProducts'],
                'permission_callback' => [$this, 'checkPermission'],
            ]
        ]);
        
        register_rest_route($this->namespace, '/products/(?P<id>\d+)', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'getProduct'],
                'permission_callback' => [$this, 'checkPermission'],
                'args'                => [
                    'id' => [
                        'validate_callback' => function ($param) {
                            return is_numeric($param);
                        }
                    ],
                ],
            ]
        ]);
        
        // Orders endpoints
        register_rest_route($this->namespace, '/orders', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'getOrders'],
                'permission_callback' => [$this, 'checkPermission'],
            ]
        ]);
        
        register_rest_route($this->namespace, '/orders/(?P<id>\d+)', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'getOrder'],
                'permission_callback' => [$this, 'checkPermission'],
                'args'                => [
                    'id' => [
                        'validate_callback' => function ($param) {
                            return is_numeric($param);
                        }
                    ],
                ],
            ]
        ]);
        
        // Sync endpoints
        register_rest_route($this->namespace, '/sync/products', [
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'syncProducts'],
                'permission_callback' => [$this, 'checkPermission'],
            ]
        ]);
        
        register_rest_route($this->namespace, '/sync/status', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'getSyncStatus'],
                'permission_callback' => [$this, 'checkPermission'],
            ]
        ]);
        
        // Process orders endpoint
        register_rest_route($this->namespace, '/process/orders', [
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'processOrders'],
                'permission_callback' => [$this, 'checkPermission'],
            ]
        ]);
        
        // Shops endpoint
        register_rest_route($this->namespace, '/shops', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'getShops'],
                'permission_callback' => [$this, 'checkPermission'],
            ]
        ]);
        
        // Categories endpoint
        register_rest_route($this->namespace, '/categories', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'getCategories'],
                'permission_callback' => [$this, 'checkPermission'],
            ]
        ]);
        
        // Sales data endpoint
        register_rest_route($this->namespace, '/sales', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'getSalesData'],
                'permission_callback' => [$this, 'checkPermission'],
            ]
        ]);
    }
    
    /**
     * Check if the user has permission to access the API
     *
     * @param WP_REST_Request $request REST request
     * @return bool Whether the user has permission
     */
    public function checkPermission(WP_REST_Request $request): bool {
        return current_user_can('manage_woocommerce');
    }
    
    /**
     * Get products from Printify
     *
     * @param WP_REST_Request $request REST request
     * @return WP_REST_Response|WP_Error Response or error
     */
    public function getProducts(WP_REST_Request $request) {
        try {
            // Get parameters
            $page = $request->get_param('page') ?? 1;
            $perPage = $request->get_param('per_page') ?? 20;
            $shopId = $request->get_param('shop_id') ?? null;
            
            // Get products from Printify
            $products = $this->printifyClient->getProducts($shopId, $page, $perPage);
            
            // Enhance products with WooCommerce data if linked
            $products = $this->enhanceProductsWithWooData($products);
            
            // Return response
            return rest_ensure_response([
                'products' => $products,
                'total' => $this->printifyClient->getProductCount($shopId),
                'page' => $page,
                'per_page' => $perPage,
            ]);
        } catch (\Exception $e) {
            return new WP_Error(
                'api_error',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }
    
    /**
     * Get a single product from Printify
     *
     * @param WP_REST_Request $request REST request
     * @return WP_REST_Response|WP_Error Response or error
     */
    public function getProduct(WP_REST_Request $request) {
        try {
            // Get parameters
            $id = $request->get_param('id');
            
            // Get product from Printify
            $product = $this->printifyClient->getProduct($id);
            
            // Get WooCommerce product ID if exists
            $wooProductId = $this->getWooProductIdByPrintifyId($id);
            
            // If linked to WooCommerce, get additional data
            if ($wooProductId) {
                $wooProduct = $this->wooClient->getProduct($wooProductId);
                $product['woo_product'] = $wooProduct;
            }
            
            // Return response
            return rest_ensure_response($product);
        } catch (\Exception $e) {
            return new WP_Error(
                'api_error',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }
    
    /**
     * Get orders from WooCommerce
     *
     * @param WP_REST_Request $request REST request
     * @return WP_REST_Response|WP_Error Response or error
     */
    public function getOrders(WP_REST_Request $request) {
        try {
            // Get parameters
            $page = $request->get_param('page') ?? 1;
            $perPage = $request->get_param('per_page') ?? 20;
            $status = $request->get_param('status') ?? null;
            
            // Get orders from WooCommerce
            $orders = $this->wooClient->getOrders($status, $page, $perPage);
            
            // Enhance orders with Printify data if linked
            $orders = $this->enhanceOrdersWithPrintifyData($orders);
            
            // Return response
            return rest_ensure_response([
                'orders' => $orders,
                'total' => $this->wooClient->getOrderCount($status),
                'page' => $page,
                'per_page' => $perPage,
            ]);
        } catch (\Exception $e) {
            return new WP_Error(
                'api_error',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }
    
    /**
     * Get a single order from WooCommerce
     *
     * @param WP_REST_Request $request REST request
     * @return WP_REST_Response|WP_Error Response or error
     */
    public function getOrder(WP_REST_Request $request) {
        try {
            // Get parameters
            $id = $request->get_param('id');
            
            // Get order from WooCommerce
            $order = $this->wooClient->getOrder($id);
            
            // Get Printify order ID if exists
            $printifyOrderId = $this->getPrintifyOrderIdByWooId($id);
            
            // If linked to Printify, get additional data
            if ($printifyOrderId) {
                $printifyOrder = $this->printifyClient->getOrder($printifyOrderId);
                $order['printify_order'] = $printifyOrder;
            }
            
            // Return response
            return rest_ensure_response($order);
        } catch (\Exception $e) {
            return new WP_Error(
                'api_error',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }
    
    /**
     * Sync products from Printify to WooCommerce
     *
     * @param WP_REST_Request $request REST request
     * @return WP_REST_Response|WP_Error Response or error
     */
    public function syncProducts(WP_REST_Request $request) {
        try {
            // Get parameters
            $productIds = $request->get_param('product_ids') ?? null;
            
            // Start sync process
            set_transient('wpwps_is_syncing', true, 5 * MINUTE_IN_SECONDS);
            
            // Get products to sync
            $products = $productIds ? 
                $this->printifyClient->getProductsByIds($productIds) : 
                $this->printifyClient->getAllProducts();
            
            $results = [
                'total' => count($products),
                'success' => 0,
                'failed' => 0,
                'created' => 0,
                'updated' => 0,
                'errors' => [],
            ];
            
            // Process each product
            foreach ($products as $product) {
                try {
                    // Check if product already exists in WooCommerce
                    $wooProductId = $this->getWooProductIdByPrintifyId($product['id']);
                    
                    if ($wooProductId) {
                        // Update existing product
                        $this->wooClient->updateProduct($wooProductId, $product);
                        $results['updated']++;
                    } else {
                        // Create new product
                        $wooProductId = $this->wooClient->createProduct($product);
                        $this->linkWooProductToPrintify($wooProductId, $product['id']);
                        $results['created']++;
                    }
                    
                    $results['success']++;
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = [
                        'product_id' => $product['id'],
                        'message' => $e->getMessage(),
                    ];
                }
            }
            
            // End sync process
            delete_transient('wpwps_is_syncing');
            
            // Update sync status
            update_option('wpwps_last_sync_time', current_time('mysql'));
            
            // Return response
            return rest_ensure_response($results);
        } catch (\Exception $e) {
            // Clean up if error
            delete_transient('wpwps_is_syncing');
            
            return new WP_Error(
                'api_error',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }
    
    /**
     * Get sync status
     *
     * @param WP_REST_Request $request REST request
     * @return WP_REST_Response|WP_Error Response or error
     */
    public function getSyncStatus(WP_REST_Request $request) {
        try {
            // Get sync status data
            $outOfSyncCount = $this->getOutOfSyncProductCount();
            $failedCount = count($this->getFailedSyncProducts());
            $lastSyncTime = get_option('wpwps_last_sync_time', '');
            $nextSyncTime = $this->getNextScheduledSync();
            $isSyncing = get_transient('wpwps_is_syncing') ? true : false;
            
            // Get sync history
            $syncHistory = $this->getSyncHistory();
            
            // Return response
            return rest_ensure_response([
                'out_of_sync' => $outOfSyncCount,
                'failed' => $failedCount,
                'last_sync' => $lastSyncTime,
                'next_sync' => $nextSyncTime,
                'is_syncing' => $isSyncing,
                'history' => $syncHistory,
            ]);
        } catch (\Exception $e) {
            return new WP_Error(
                'api_error',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }
    
    /**
     * Process orders in Printify
     *
     * @param WP_REST_Request $request REST request
     * @return WP_REST_Response|WP_Error Response or error
     */
    public function processOrders(WP_REST_Request $request) {
        try {
            // Get parameters
            $orderIds = $request->get_param('order_ids') ?? null;
            
            // Get orders to process
            $orders = $orderIds ? 
                $this->wooClient->getOrdersByIds($orderIds) : 
                $this->wooClient->getPendingOrders();
            
            $results = [
                'total' => count($orders),
                'success' => 0,
                'failed' => 0,
                'errors' => [],
            ];
            
            // Process each order
            foreach ($orders as $order) {
                try {
                    // Send order to Printify
                    $printifyOrderId = $this->printifyClient->createOrder($order);
                    
                    // Link WooCommerce order to Printify
                    $this->linkWooOrderToPrintify($order['id'], $printifyOrderId);
                    
                    // Update order status in WooCommerce
                    $this->wooClient->updateOrderStatus($order['id'], 'processing');
                    
                    $results['success']++;
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = [
                        'order_id' => $order['id'],
                        'message' => $e->getMessage(),
                    ];
                }
            }
            
            // Return response
            return rest_ensure_response($results);
        } catch (\Exception $e) {
            return new WP_Error(
                'api_error',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }
    
    /**
     * Get Printify shops
     *
     * @param WP_REST_Request $request REST request
     * @return WP_REST_Response|WP_Error Response or error
     */
    public function getShops(WP_REST_Request $request) {
        try {
            // Get shops from Printify
            $shops = $this->printifyClient->getShops();
            
            // Return response
            return rest_ensure_response($shops);
        } catch (\Exception $e) {
            return new WP_Error(
                'api_error',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }
    
    /**
     * Get product categories from WooCommerce
     *
     * @param WP_REST_Request $request REST request
     * @return WP_REST_Response|WP_Error Response or error
     */
    public function getCategories(WP_REST_Request $request) {
        try {
            // Get categories from WooCommerce
            $categories = $this->wooClient->getCategories();
            
            // Return response
            return rest_ensure_response($categories);
        } catch (\Exception $e) {
            return new WP_Error(
                'api_error',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }
    
    /**
     * Get sales data for dashboard
     *
     * @param WP_REST_Request $request REST request
     * @return WP_REST_Response|WP_Error Response or error
     */
    public function getSalesData(WP_REST_Request $request) {
        try {
            // Get parameters
            $period = $request->get_param('period') ?? 'week';
            
            // Get sales data from WooCommerce
            $salesData = $this->wooClient->getSalesData($period);
            
            // Return response
            return rest_ensure_response($salesData);
        } catch (\Exception $e) {
            return new WP_Error(
                'api_error',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }
    
    /**
     * Enhance products with WooCommerce data
     *
     * @param array $products Products from Printify
     * @return array Enhanced products
     */
    private function enhanceProductsWithWooData(array $products): array {
        foreach ($products as &$product) {
            // Get WooCommerce product ID if exists
            $wooProductId = $this->getWooProductIdByPrintifyId($product['id']);
            
            if ($wooProductId) {
                // Get basic WooCommerce product data
                try {
                    $wooProduct = $this->wooClient->getProductBasicData($wooProductId);
                    $product['woo_product_id'] = $wooProductId;
                    $product['woo_status'] = $wooProduct['status'];
                    $product['woo_price'] = $wooProduct['price'];
                    $product['woo_permalink'] = $wooProduct['permalink'];
                    $product['is_synced'] = true;
                } catch (\Exception $e) {
                    $product['is_synced'] = false;
                }
            } else {
                $product['is_synced'] = false;
            }
        }
        
        return $products;
    }
    
    /**
     * Enhance orders with Printify data
     *
     * @param array $orders Orders from WooCommerce
     * @return array Enhanced orders
     */
    private function enhanceOrdersWithPrintifyData(array $orders): array {
        foreach ($orders as &$order) {
            // Get Printify order ID if exists
            $printifyOrderId = $this->getPrintifyOrderIdByWooId($order['id']);
            
            if ($printifyOrderId) {
                // Get basic Printify order data
                try {
                    $printifyOrder = $this->printifyClient->getOrderBasicData($printifyOrderId);
                    $order['printify_order_id'] = $printifyOrderId;
                    $order['printify_status'] = $printifyOrder['status'];
                    $order['is_processed'] = true;
                } catch (\Exception $e) {
                    $order['is_processed'] = false;
                }
            } else {
                $order['is_processed'] = false;
            }
        }
        
        return $orders;
    }
    
    /**
     * Get WooCommerce product ID by Printify ID
     *
     * @param string $printifyId Printify product ID
     * @return int|null WooCommerce product ID or null if not found
     */
    private function getWooProductIdByPrintifyId(string $printifyId): ?int {
        global $wpdb;
        
        $result = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_printify_product_id' AND meta_value = %s LIMIT 1",
                $printifyId
            )
        );
        
        return $result ? (int) $result : null;
    }
    
    /**
     * Get Printify order ID by WooCommerce ID
     *
     * @param int $wooId WooCommerce order ID
     * @return string|null Printify order ID or null if not found
     */
    private function getPrintifyOrderIdByWooId(int $wooId): ?string {
        $printifyOrderId = get_post_meta($wooId, '_printify_order_id', true);
        
        return $printifyOrderId ?: null;
    }
    
    /**
     * Link WooCommerce product to Printify
     *
     * @param int $wooId WooCommerce product ID
     * @param string $printifyId Printify product ID
     * @return void
     */
    private function linkWooProductToPrintify(int $wooId, string $printifyId): void {
        update_post_meta($wooId, '_printify_product_id', $printifyId);
    }
    
    /**
     * Link WooCommerce order to Printify
     *
     * @param int $wooId WooCommerce order ID
     * @param string $printifyId Printify order ID
     * @return void
     */
    private function linkWooOrderToPrintify(int $wooId, string $printifyId): void {
        update_post_meta($wooId, '_printify_order_id', $printifyId);
    }
    
    /**
     * Get count of out-of-sync products
     *
     * @return int Count of out-of-sync products
     */
    private function getOutOfSyncProductCount(): int {
        // This would check for products that need syncing
        // For now, return a placeholder
        return 3;
    }
    
    /**
     * Get products that failed to sync
     *
     * @return array Failed products
     */
    private function getFailedSyncProducts(): array {
        // This would check the sync log for failed products
        // For now, return a placeholder
        return [];
    }
    
    /**
     * Get next scheduled synchronization time
     *
     * @return string Next sync time
     */
    private function getNextScheduledSync(): string {
        // Check if we have a scheduled event
        $timestamp = wp_next_scheduled('wpwps_product_sync');
        if ($timestamp) {
            return date('Y-m-d H:i:s', $timestamp);
        }
        
        return 'Not scheduled';
    }
    
    /**
     * Get sync history
     *
     * @param int $days Number of days to retrieve
     * @return array Sync history
     */
    private function getSyncHistory(int $days = 7): array {
        // This would get the sync history from the database
        // For now, return placeholder data
        $history = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $history[] = [
                'date' => $date,
                'successful' => rand(5, 20),
                'failed' => rand(0, 3),
                'duration' => rand(30, 120)
            ];
        }
        return $history;
    }
}
