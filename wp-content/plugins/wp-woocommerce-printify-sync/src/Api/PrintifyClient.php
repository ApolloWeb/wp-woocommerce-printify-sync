<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Api;

class PrintifyClient
{
    /**
     * Printify API base URL.
     * 
     * @var string
     */
    protected string $apiBaseUrl = 'https://api.printify.com/v1';
    
    /**
     * API key for authentication.
     * 
     * @var string
     */
    protected string $apiKey;
    
    /**
     * Shop ID to use in API requests.
     * 
     * @var string
     */
    protected string $shopId;
    
    /**
     * Constructor.
     * 
     * @param string $apiKey
     * @param string $shopId
     */
    public function __construct(string $apiKey, string $shopId = '')
    {
        $this->apiKey = $apiKey;
        $this->shopId = $shopId;
    }
    
    /**
     * Test API connection.
     * 
     * @return bool
     * @throws \Exception
     */
    public function testConnection(): bool
    {
        try {
            $response = $this->makeRequest('shops');
            return !empty($response) && isset($response['shops']);
        } catch (\Exception $e) {
            throw new \Exception(__('API connection failed: ', 'wp-woocommerce-printify-sync') . $e->getMessage());
        }
    }
    
    /**
     * Get shops from Printify.
     * 
     * @return array
     * @throws \Exception
     */
    public function getShops(): array
    {
        try {
            $response = $this->makeRequest('shops');
            return $response['shops'] ?? [];
        } catch (\Exception $e) {
            throw new \Exception(__('Failed to fetch shops: ', 'wp-woocommerce-printify-sync') . $e->getMessage());
        }
    }
    
    /**
     * Sync products from Printify to WooCommerce.
     * 
     * @param int $page Page number
     * @param int $limit Number of products per page
     * @return array
     * @throws \Exception
     */
    public function syncProducts(int $page = 1, int $limit = 10): array
    {
        $this->validateShopId();
        
        try {
            $endpoint = "shops/{$this->shopId}/products.json";
            $queryParams = [
                'page' => $page,
                'limit' => $limit,
            ];
            
            $response = $this->makeRequest($endpoint, 'GET', null, $queryParams);
            
            if (!isset($response['products'])) {
                throw new \Exception(__('Invalid API response', 'wp-woocommerce-printify-sync'));
            }
            
            $products = $response['products'];
            $processedProducts = [];
            
            foreach ($products as $product) {
                $processedProduct = $this->processAndSaveProduct($product);
                if ($processedProduct) {
                    $processedProducts[] = $processedProduct;
                    
                    // Log successful product sync
                    $this->logSyncEvent('product', sprintf(
                        __('Product "%s" (ID: %s) synced successfully.', 'wp-woocommerce-printify-sync'),
                        $product['title'],
                        $product['id']
                    ), 'success');
                }
            }
            
            return [
                'products' => $processedProducts,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => ceil($response['total'] / $limit),
                    'total_items' => $response['total'],
                ]
            ];
        } catch (\Exception $e) {
            // Log error
            $this->logSyncEvent('product', $e->getMessage(), 'error');
            
            throw new \Exception(__('Failed to sync products: ', 'wp-woocommerce-printify-sync') . $e->getMessage());
        }
    }
    
    /**
     * Sync inventory from Printify to WooCommerce.
     * 
     * @return array
     * @throws \Exception
     */
    public function syncInventory(): array
    {
        $this->validateShopId();
        
        try {
            // Get all WooCommerce products with Printify metadata
            $args = [
                'post_type' => 'product',
                'posts_per_page' => -1,
                'meta_query' => [
                    [
                        'key' => '_wpwps_printify_id',
                        'compare' => 'EXISTS',
                    ],
                ],
            ];
            
            $query = new \WP_Query($args);
            $updatedCount = 0;
            
            foreach ($query->posts as $post) {
                $printifyId = get_post_meta($post->ID, '_wpwps_printify_id', true);
                
                if (!$printifyId) {
                    continue;
                }
                
                $endpoint = "shops/{$this->shopId}/products/{$printifyId}.json";
                $productData = $this->makeRequest($endpoint);
                
                if (!$productData || !isset($productData['variants'])) {
                    continue;
                }
                
                $this->updateWooCommerceProductInventory($post->ID, $productData);
                $updatedCount++;
                
                // Log successful inventory sync
                $this->logSyncEvent('inventory', sprintf(
                    __('Inventory for product "%s" (ID: %s) synced successfully.', 'wp-woocommerce-printify-sync'),
                    $post->post_title,
                    $post->ID
                ), 'success');
            }
            
            return [
                'count' => $updatedCount,
            ];
        } catch (\Exception $e) {
            // Log error
            $this->logSyncEvent('inventory', $e->getMessage(), 'error');
            
            throw new \Exception(__('Failed to sync inventory: ', 'wp-woocommerce-printify-sync') . $e->getMessage());
        }
    }
    
    /**
     * Sync orders from WooCommerce to Printify.
     * 
     * @param string $timeframe Timeframe for orders (e.g. 24h, 7d, 30d)
     * @return array
     * @throws \Exception
     */
    public function syncOrders(string $timeframe = '24h'): array
    {
        $this->validateShopId();
        
        try {
            // Get timestamp based on timeframe
            $timestamp = $this->getTimestampFromTimeframe($timeframe);
            
            // Get WooCommerce orders that contain Printify products
            $args = [
                'post_type' => 'shop_order',
                'post_status' => 'wc-processing',
                'date_query' => [
                    [
                        'after' => date('Y-m-d H:i:s', $timestamp),
                        'inclusive' => true,
                    ],
                ],
                'posts_per_page' => -1,
            ];
            
            $query = new \WP_Query($args);
            $syncedOrders = [];
            
            foreach ($query->posts as $post) {
                $order = wc_get_order($post->ID);
                
                if (!$order) {
                    continue;
                }
                
                $printifyItems = $this->getOrderPrintifyItems($order);
                
                if (empty($printifyItems)) {
                    continue;
                }
                
                $syncedOrder = $this->createPrintifyOrder($order, $printifyItems);
                
                if ($syncedOrder) {
                    $syncedOrders[] = $syncedOrder;
                    
                    // Log successful order sync
                    $this->logSyncEvent('order', sprintf(
                        __('Order #%s synced to Printify successfully (Printify Order ID: %s).', 'wp-woocommerce-printify-sync'),
                        $order->get_order_number(),
                        $syncedOrder['printify_id']
                    ), 'success');
                }
            }
            
            return [
                'count' => count($syncedOrders),
                'orders' => $syncedOrders,
            ];
        } catch (\Exception $e) {
            // Log error
            $this->logSyncEvent('order', $e->getMessage(), 'error');
            
            throw new \Exception(__('Failed to sync orders: ', 'wp-woocommerce-printify-sync') . $e->getMessage());
        }
    }
    
    /**
     * Make a request to the Printify API.
     * 
     * @param string $endpoint API endpoint
     * @param string $method HTTP method (GET, POST, PUT, DELETE)
     * @param array|null $data Request data
     * @param array $queryParams Query parameters
     * @return array
     * @throws \Exception
     */
    protected function makeRequest(string $endpoint, string $method = 'GET', array $data = null, array $queryParams = []): array
    {
        $url = $this->apiBaseUrl . '/' . $endpoint;
        
        // Add query parameters
        if (!empty($queryParams)) {
            $url = add_query_arg($queryParams, $url);
        }
        
        $args = [
            'method' => $method,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'timeout' => 30,
        ];
        
        if ($data !== null && in_array($method, ['POST', 'PUT'])) {
            $args['body'] = json_encode($data);
        }
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            throw new \Exception($response->get_error_message());
        }
        
        $responseCode = wp_remote_retrieve_response_code($response);
        $responseBody = wp_remote_retrieve_body($response);
        $responseData = json_decode($responseBody, true);
        
        if ($responseCode < 200 || $responseCode >= 300) {
            $errorMessage = isset($responseData['message']) ? $responseData['message'] : 'Unknown API error';
            throw new \Exception("API Error ({$responseCode}): {$errorMessage}");
        }
        
        return $responseData;
    }
    
    /**
     * Process and save a Printify product to WooCommerce.
     * 
     * @param array $product Printify product data
     * @return array|false
     */
    protected function processAndSaveProduct(array $product)
    {
        // Check if product already exists in WooCommerce
        $existingProductId = $this->getWooCommerceProductByPrintifyId($product['id']);
        
        // Product data for WooCommerce
        $productData = [
            'name' => $product['title'],
            'description' => $product['description'] ?? '',
            'short_description' => $product['description'] ?? '',
            'status' => 'publish',
            'catalog_visibility' => 'visible',
            'featured' => false,
            'type' => $this->getProductType($product),
            'virtual' => false,
            'downloadable' => false,
            'meta_data' => [
                [
                    'key' => '_wpwps_printify_id',
                    'value' => $product['id'],
                ],
                [
                    'key' => '_wpwps_printify_data',
                    'value' => wp_json_encode($product),
                ],
                [
                    'key' => '_wpwps_last_sync',
                    'value' => current_time('mysql'),
                ],
            ],
        ];
        
        // Add images
        if (!empty($product['images'])) {
            $productData['images'] = array_map(function($image) {
                return [
                    'src' => $image['src'],
                    'position' => $image['position'] ?? 0,
                ];
            }, $product['images']);
        }
        
        // Create or update the WooCommerce product
        if ($existingProductId) {
            $productData['id'] = $existingProductId;
            $wooProduct = wc_update_product($productData);
        } else {
            $wooProduct = wc_create_product($productData);
        }
        
        if (is_wp_error($wooProduct)) {
            return false;
        }
        
        $productId = $wooProduct->get_id();
        
        // Process variants
        if (isset($product['variants']) && !empty($product['variants'])) {
            $this->processProductVariants($productId, $product);
        }
        
        // Process categories
        if (isset($product['tags']) && !empty($product['tags'])) {
            wp_set_object_terms($productId, $product['tags'], 'product_tag');
        }
        
        return [
            'id' => $productId,
            'printify_id' => $product['id'],
            'title' => $product['title'],
            'type' => $this->getProductType($product),
        ];
    }
    
    /**
     * Process product variants.
     * 
     * @param int $productId WooCommerce product ID
     * @param array $product Printify product data
     * @return void
     */
    protected function processProductVariants(int $productId, array $product): void
    {
        if (!isset($product['variants']) || empty($product['variants'])) {
            return;
        }
        
        $wooProduct = wc_get_product($productId);
        
        if (!$wooProduct) {
            return;
        }
        
        // Simple product with single variant
        if (count($product['variants']) === 1) {
            $variant = $product['variants'][0];
            
            // Set price
            $wooProduct->set_regular_price($variant['price'] / 100); // Printify prices are in cents
            
            // Set SKU
            if (!empty($variant['sku'])) {
                $wooProduct->set_sku($variant['sku']);
            }
            
            // Set stock
            if (isset($variant['quantity'])) {
                $wooProduct->set_manage_stock(true);
                $wooProduct->set_stock_quantity($variant['quantity']);
                $wooProduct->set_stock_status($variant['quantity'] > 0 ? 'instock' : 'outofstock');
            }
            
            $wooProduct->save();
        }
        // Variable product with multiple variants
        else {
            // Convert Printify product to WooCommerce variable product
            if ($wooProduct->get_type() !== 'variable') {
                // Convert to variable if it's not already
                wp_set_object_terms($productId, 'variable', 'product_type');
            }
            
            // Create attributes from variants
            $attributes = $this->extractAttributesFromVariants($product);
            
            // Set attributes
            $productAttributes = [];
            foreach ($attributes as $attributeName => $terms) {
                $attributeKey = sanitize_title($attributeName);
                $productAttributes[$attributeKey] = $this->createProductAttribute($attributeName, $terms);
            }
            
            $wooProduct = wc_get_product($productId); // Refresh product object
            $wooProduct->set_attributes($productAttributes);
            $wooProduct->save();
            
            // Create variations
            $this->createProductVariations($wooProduct, $product['variants'], $attributes);
        }
    }
    
    /**
     * Extract attributes from variants.
     * 
     * @param array $product Printify product data
     * @return array
     */
    protected function extractAttributesFromVariants(array $product): array
    {
        $attributes = [];
        
        if (!isset($product['options']) || empty($product['options'])) {
            return $attributes;
        }
        
        foreach ($product['options'] as $option) {
            $name = $option['name'];
            $values = [];
            
            foreach ($option['values'] as $value) {
                $values[] = $value['title'];
            }
            
            $attributes[$name] = array_unique($values);
        }
        
        return $attributes;
    }
    
    /**
     * Create product attribute.
     * 
     * @param string $name Attribute name
     * @param array $terms Attribute terms
     * @return array
     */
    protected function createProductAttribute(string $name, array $terms): array
    {
        return [
            'name' => $name,
            'value' => implode('|', $terms),
            'position' => 0,
            'is_visible' => 1,
            'is_variation' => 1,
            'is_taxonomy' => 0,
        ];
    }
    
    /**
     * Create product variations.
     * 
     * @param \WC_Product_Variable $product WooCommerce variable product
     * @param array $variants Printify variants
     * @param array $attributes Product attributes
     * @return void
     */
    protected function createProductVariations(\WC_Product_Variable $product, array $variants, array $attributes): void
    {
        // Remove existing variations
        foreach ($product->get_children() as $childId) {
            wp_delete_post($childId, true);
        }
        
        foreach ($variants as $variant) {
            $variation = new \WC_Product_Variation();
            $variation->set_parent_id($product->get_id());
            
            // Set variant attributes
            $variantAttributes = [];
            if (isset($variant['options'])) {
                foreach ($variant['options'] as $optionId => $valueId) {
                    if (isset($product['options'][$optionId]) && isset($product['options'][$optionId]['values'][$valueId])) {
                        $optionName = $product['options'][$optionId]['name'];
                        $valueName = $product['options'][$optionId]['values'][$valueId]['title'];
                        $variantAttributes['attribute_' . sanitize_title($optionName)] = $valueName;
                    }
                }
            }
            $variation->set_attributes($variantAttributes);
            
            // Set prices
            $variation->set_regular_price($variant['price'] / 100); // Printify prices are in cents
            
            // Set SKU
            if (!empty($variant['sku'])) {
                $variation->set_sku($variant['sku']);
            }
            
            // Set stock
            if (isset($variant['quantity'])) {
                $variation->set_manage_stock(true);
                $variation->set_stock_quantity($variant['quantity']);
                $variation->set_stock_status($variant['quantity'] > 0 ? 'instock' : 'outofstock');
            }
            
            // Save variation
            $variation->save();
        }
    }
    
    /**
     * Update WooCommerce product inventory.
     * 
     * @param int $productId WooCommerce product ID
     * @param array $productData Printify product data
     * @return void
     */
    protected function updateWooCommerceProductInventory(int $productId, array $productData): void
    {
        $product = wc_get_product($productId);
        
        if (!$product) {
            return;
        }
        
        if ($product->get_type() === 'simple') {
            if (isset($productData['variants'][0]['quantity'])) {
                $quantity = $productData['variants'][0]['quantity'];
                $product->set_manage_stock(true);
                $product->set_stock_quantity($quantity);
                $product->set_stock_status($quantity > 0 ? 'instock' : 'outofstock');
                $product->save();
            }
        } else if ($product->get_type() === 'variable') {
            foreach ($product->get_children() as $variationId) {
                $variation = wc_get_product($variationId);
                $printifyVariantId = get_post_meta($variationId, '_wpwps_printify_variant_id', true);
                
                if (!$variation || !$printifyVariantId) {
                    continue;
                }
                
                // Find matching Printify variant
                $printifyVariant = null;
                foreach ($productData['variants'] as $variant) {
                    if ($variant['id'] == $printifyVariantId) {
                        $printifyVariant = $variant;
                        break;
                    }
                }
                
                if ($printifyVariant && isset($printifyVariant['quantity'])) {
                    $quantity = $printifyVariant['quantity'];
                    $variation->set_manage_stock(true);
                    $variation->set_stock_quantity($quantity);
                    $variation->set_stock_status($quantity > 0 ? 'instock' : 'outofstock');
                    $variation->save();
                }
            }
        }
        
        // Update last sync time
        update_post_meta($productId, '_wpwps_last_sync', current_time('mysql'));
    }
    
    /**
     * Get WooCommerce product by Printify ID.
     * 
     * @param string $printifyId Printify product ID
     * @return int|false
     */
    protected function getWooCommerceProductByPrintifyId(string $printifyId)
    {
        $args = [
            'post_type' => 'product',
            'meta_query' => [
                [
                    'key' => '_wpwps_printify_id',
                    'value' => $printifyId,
                ],
            ],
            'posts_per_page' => 1,
            'fields' => 'ids',
        ];
        
        $posts = get_posts($args);
        
        return !empty($posts) ? (int)$posts[0] : false;
    }
    
    /**
     * Get product type based on variants count.
     * 
     * @param array $product Printify product data
     * @return string
     */
    protected function getProductType(array $product): string
    {
        if (!isset($product['variants']) || count($product['variants']) <= 1) {
            return 'simple';
        }
        
        return 'variable';
    }
    
    /**
     * Get order items that are Printify products.
     * 
     * @param \WC_Order $order WooCommerce order
     * @return array
     */
    protected function getOrderPrintifyItems(\WC_Order $order): array
    {
        $printifyItems = [];
        
        foreach ($order->get_items() as $item) {
            $productId = $item->get_product_id();
            $variationId = $item->get_variation_id();
            $printifyId = get_post_meta($productId, '_wpwps_printify_id', true);
            
            if (!$printifyId) {
                continue;
            }
            
            $printifyVariantId = '';
            if ($variationId) {
                $printifyVariantId = get_post_meta($variationId, '_wpwps_printify_variant_id', true);
            } else {
                // For simple products, get the first variant ID
                $printifyData = json_decode(get_post_meta($productId, '_wpwps_printify_data', true), true);
                if ($printifyData && isset($printifyData['variants'][0]['id'])) {
                    $printifyVariantId = $printifyData['variants'][0]['id'];
                }
            }
            
            if (!$printifyVariantId) {
                continue;
            }
            
            $printifyItems[] = [
                'product_id' => $printifyId,
                'variant_id' => $printifyVariantId,
                'quantity' => $item->get_quantity(),
            ];
        }
        
        return $printifyItems;
    }
    
    /**
     * Create Printify order.
     * 
     * @param \WC_Order $order WooCommerce order
     * @param array $printifyItems Printify line items
     * @return array|false
     */
    protected function createPrintifyOrder(\WC_Order $order, array $printifyItems)
    {
        // Check if order already exists in Printify
        $printifyOrderId = get_post_meta($order->get_id(), '_wpwps_printify_order_id', true);
        if ($printifyOrderId) {
            return [
                'order_id' => $order->get_id(),
                'printify_id' => $printifyOrderId,
                'status' => 'already_exists',
            ];
        }
        
        // Create address data
        $shipping = $order->get_shipping_address_1();
        if ($order->get_shipping_address_2()) {
            $shipping .= ', ' . $order->get_shipping_address_2();
        }
        
        $orderData = [
            'external_id' => $order->get_order_number(),
            'line_items' => $printifyItems,
            'shipping_method' => 'standard',
            'shipping_address' => [
                'first_name' => $order->get_shipping_first_name(),
                'last_name' => $order->get_shipping_last_name(),
                'email' => $order->get_billing_email(),
                'phone' => $order->get_billing_phone(),
                'address1' => $shipping,
                'city' => $order->get_shipping_city(),
                'region' => $order->get_shipping_state(),
                'country' => $order->get_shipping_country(),
                'zip' => $order->get_shipping_postcode(),
            ],
            'send_shipping_notification' => true,
        ];
        
        try {
            $endpoint = "shops/{$this->shopId}/orders.json";
            $response = $this->makeRequest($endpoint, 'POST', $orderData);
            
            if (isset($response['id'])) {
                // Save Printify order ID to WooCommerce order
                update_post_meta($order->get_id(), '_wpwps_printify_order_id', $response['id']);
                update_post_meta($order->get_id(), '_wpwps_printify_order_data', wp_json_encode($response));
                update_post_meta($order->get_id(), '_wpwps_printify_order_status', $response['status']);
                
                // Add note to order
                $order->add_order_note(
                    sprintf(__('Order created in Printify (ID: %s)', 'wp-woocommerce-printify-sync'), $response['id'])
                );
                
                return [
                    'order_id' => $order->get_id(),
                    'printify_id' => $response['id'],
                    'status' => 'created',
                ];
            }
            
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Log a sync event.
     * 
     * @param string $type Event type (product, inventory, order)
     * @param string $message Event message
     * @param string $status Event status (success, error)
     * @param array $data Additional data
     * @return void
     */
    protected function logSyncEvent(string $type, string $message, string $status, array $data = []): void
    {
        global $wpdb;
        
        $table = $wpdb->prefix . 'wpwps_sync_logs';
        
        $wpdb->insert(
            $table,
            [
                'time' => current_time('mysql'),
                'type' => $type,
                'message' => $message,
                'status' => $status,
                'data' => !empty($data) ? wp_json_encode($data) : null,
            ],
            [
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
            ]
        );
    }
    
    /**
     * Validate that a shop ID is set.
     * 
     * @throws \Exception
     */
    protected function validateShopId(): void
    {
        if (empty($this->shopId)) {
            throw new \Exception(__('Shop ID is required.', 'wp-woocommerce-printify-sync'));
        }
    }
    
    /**
     * Get timestamp from timeframe string.
     * 
     * @param string $timeframe Timeframe (e.g. 24h, 7d, 30d)
     * @return int Timestamp
     */
    protected function getTimestampFromTimeframe(string $timeframe): int
    {
        $unit = substr($timeframe, -1);
        $value = (int)substr($timeframe, 0, -1);
        
        switch ($unit) {
            case 'h':
                return strtotime("-{$value} hours");
            case 'd':
                return strtotime("-{$value} days");
            case 'w':
                return strtotime("-{$value} weeks");
            case 'm':
                return strtotime("-{$value} months");
            default:
                return strtotime('-24 hours');
        }
    }
}