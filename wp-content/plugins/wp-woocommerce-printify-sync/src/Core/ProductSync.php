<?php
/**
 * Product sync functionality.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Core
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Core;

use ApolloWeb\WPWooCommercePrintifySync\Admin\Settings;
use ApolloWeb\WPWooCommercePrintifySync\API\PrintifyAPI;

/**
 * ProductSync class.
 */
class ProductSync {
    /**
     * The API instance.
     *
     * @var PrintifyAPI
     */
    private $api;

    /**
     * The settings instance.
     *
     * @var Settings
     */
    private $settings;

    /**
     * Constructor.
     *
     * @param PrintifyAPI $api The API instance.
     */
    public function __construct($api) {
        $this->api = $api;
        $this->settings = new Settings();
        
        // Schedule sync if enabled
        add_action('init', [$this, 'scheduleSync']);
        
        // Add hook for scheduled sync
        add_action('wpwps_daily_sync', [$this, 'syncProductsScheduled']);
    }

    /**
     * Schedule the sync if auto sync is enabled.
     *
     * @return void
     */
    public function scheduleSync() {
        if ($this->settings->getOption('auto_sync')) {
            $interval = $this->settings->getOption('sync_interval');
            
            if (!wp_next_scheduled('wpwps_daily_sync')) {
                wp_schedule_event(time(), $interval, 'wpwps_daily_sync');
            }
        } else {
            wp_clear_scheduled_hook('wpwps_daily_sync');
        }
    }

    /**
     * Sync products from Printify.
     *
     * @return void
     */
    public function syncProducts() {
        check_ajax_referer('wpwps-nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'wp-woocommerce-printify-sync')]);
        }
        
        $shop_id = isset($_POST['shop_id']) ? intval($_POST['shop_id']) : 0;
        
        if (!$shop_id) {
            wp_send_json_error(['message' => __('Shop ID is required.', 'wp-woocommerce-printify-sync')]);
        }
        
        $result = $this->syncProductsFromShop($shop_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        } else {
            wp_send_json_success([
                'message' => sprintf(
                    /* translators: %d: number of products */
                    __('%d products synced successfully!', 'wp-woocommerce-printify-sync'),
                    count($result)
                ),
                'products' => $result,
            ]);
        }
    }

    /**
     * Sync products scheduled task.
     *
     * @return void
     */
    public function syncProductsScheduled() {
        $shops = $this->api->getShops();
        
        if (is_wp_error($shops)) {
            $this->log('Error getting shops: ' . $shops->get_error_message());
            return;
        }
        
        foreach ($shops as $shop) {
            $this->syncProductsFromShop($shop['id']);
        }
    }

    /**
     * Sync products from a shop.
     *
     * @param int $shop_id The shop ID.
     * @return array|WP_Error
     */
    private function syncProductsFromShop($shop_id) {
        $products = $this->api->getProducts($shop_id);
        
        if (is_wp_error($products)) {
            $this->log('Error getting products: ' . $products->get_error_message());
            return $products;
        }
        
        $synced_products = [];
        
        foreach ($products as $product) {
            $result = $this->createOrUpdateProduct($shop_id, $product);
            
            if (!is_wp_error($result)) {
                $synced_products[] = $result;
            } else {
                $this->log('Error syncing product ' . $product['id'] . ': ' . $result->get_error_message());
            }
        }
        
        return $synced_products;
    }

    /**
     * Create or update a WooCommerce product.
     *
     * @param int   $shop_id The shop ID.
     * @param array $product The product data.
     * @return int|WP_Error
     */
    private function createOrUpdateProduct($shop_id, $product) {
        // Get detailed product info
        $product_details = $this->api->getProduct($shop_id, $product['id']);
        
        if (is_wp_error($product_details)) {
            return $product_details;
        }
        
        // Check if product exists
        $existing_product = $this->getExistingProduct($product['id']);
        
        // Prepare product data
        $product_data = [
            'name'              => $product_details['title'],
            'status'            => $this->settings->getOption('product_status'),
            'description'       => $product_details['description'] ?? '',
            'short_description' => $product_details['description'] ?? '',
            'sku'               => 'PRINTIFY-' . $product_details['id'],
            'regular_price'     => number_format((float) $product_details['variants'][0]['price'] / 100, 2),
            'meta_data'         => [
                [
                    'key'   => '_printify_id',
                    'value' => $product_details['id'],
                ],
                [
                    'key'   => '_printify_shop_id',
                    'value' => $shop_id,
                ],
                [
                    'key'   => '_printify_data',
                    'value' => json_encode($product_details),
                ],
            ],
        ];
        
        // If the product has variants
        if (count($product_details['variants']) > 1) {
            $product_data['type'] = 'variable';
            
            // Get attributes from variants
            $attributes = $this->extractProductAttributes($product_details);
            
            if (!empty($attributes)) {
                $product_data['attributes'] = $attributes;
            }
        } else {
            $product_data['type'] = 'simple';
        }
        
        // Create or update product
        if ($existing_product) {
            $product_data['id'] = $existing_product;
            $wc_product = wc_update_product($product_data);
        } else {
            $wc_product = wc_create_product($product_data);
        }
        
        if (is_wp_error($wc_product)) {
            return $wc_product;
        }
        
        $product_id = $wc_product->get_id();
        
        // Import images if enabled
        if ($this->settings->getOption('import_images') && !empty($product_details['images'])) {
            $this->importProductImages($product_id, $product_details['images']);
        }
        
        // If variable product, create variations
        if ($product_data['type'] === 'variable') {
            $this->createProductVariations($product_id, $shop_id, $product_details);
        }
        
        return $product_id;
    }

    /**
     * Get the existing product ID by Printify ID.
     *
     * @param int $printify_id The Printify product ID.
     * @return int|false
     */
    private function getExistingProduct($printify_id) {
        global $wpdb;
        
        $product_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_printify_id' AND meta_value = %s LIMIT 1",
                $printify_id
            )
        );
        
        return $product_id ? (int) $product_id : false;
    }

    /**
     * Extract product attributes from product details.
     *
     * @param array $product_details The product details.
     * @return array
     */
    private function extractProductAttributes($product_details) {
        $attributes = [];
        $options = [];
        
        foreach ($product_details['variants'] as $variant) {
            if (!empty($variant['options'])) {
                foreach ($variant['options'] as $option_name => $option_value) {
                    if (!isset($options[$option_name])) {
                        $options[$option_name] = [];
                    }
                    
                    if (!in_array($option_value, $options[$option_name], true)) {
                        $options[$option_name][] = $option_value;
                    }
                }
            }
        }
        
        foreach ($options as $name => $values) {
            $attributes[] = [
                'name'      => $name,
                'position'  => 0,
                'visible'   => true,
                'variation' => true,
                'options'   => $values,
            ];
        }
        
        return $attributes;
    }

    /**
     * Import product images.
     *
     * @param int   $product_id The product ID.
     * @param array $images     The images.
     * @return void
     */
    private function importProductImages($product_id, $images) {
        $product = wc_get_product($product_id);
        
        if (!$product) {
            return;
        }
        
        // Set featured image
        if (!empty($images[0]['src'])) {
            $this->setProductImage($product_id, $images[0]['src'], true);
        }
        
        // Set gallery images
        $gallery_image_ids = [];
        
        for ($i = 1; $i < count($images); $i++) {
            if (!empty($images[$i]['src'])) {
                $attachment_id = $this->setProductImage($product_id, $images[$i]['src'], false);
                
                if ($attachment_id) {
                    $gallery_image_ids[] = $attachment_id;
                }
            }
        }
        
        if (!empty($gallery_image_ids)) {
            $product->set_gallery_image_ids($gallery_image_ids);
            $product->save();
        }
    }

    /**
     * Set product image.
     *
     * @param int    $product_id    The product ID.
     * @param string $image_url     The image URL.
     * @param bool   $featured_image Whether to set as featured image.
     * @return int|false
     */
    private function setProductImage($product_id, $image_url, $featured_image = false) {
        $upload_dir = wp_upload_dir();
        $image_name = basename($image_url);
        
        // Check if image already exists
        $attachment_id = $this->getAttachmentIdByUrl($image_url);
        
        if ($attachment_id) {
            if ($featured_image) {
                set_post_thumbnail($product_id, $attachment_id);
            }
            
            return $attachment_id;
        }
        
        // Get the file
        $image_data = wp_remote_get($image_url);
        
        if (is_wp_error($image_data) || 200 !== wp_remote_retrieve_response_code($image_data)) {
            return false;
        }
        
        $image_data = wp_remote_retrieve_body($image_data);
        
        // Upload the file
        $file_path = $upload_dir['path'] . '/' . $image_name;
        file_put_contents($file_path, $image_data);
        
        $attachment = [
            'post_mime_type' => mime_content_type($file_path),
            'post_title'     => preg_replace('/\.[^.]+$/', '', $image_name),
            'post_content'   => '',
            'post_status'    => 'inherit',
            'guid'           => $upload_dir['url'] . '/' . $image_name,
        ];
        
        $attachment_id = wp_insert_attachment($attachment, $file_path, $product_id);
        
        if (!is_wp_error($attachment_id)) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
            $attachment_data = wp_generate_attachment_metadata($attachment_id, $file_path);
            wp_update_attachment_metadata($attachment_id, $attachment_data);
            
            if ($featured_image) {
                set_post_thumbnail($product_id, $attachment_id);
            }
            
            return $attachment_id;
        }
        
        return false;
    }

    /**
     * Get attachment ID by URL.
     *
     * @param string $url The URL.
     * @return int|false
     */
    private function getAttachmentIdByUrl($url) {
        global $wpdb;
        
        $attachment_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE guid = %s OR guid = %s LIMIT 1",
                $url,
                $url . '?'
            )
        );
        
        return $attachment_id ? (int) $attachment_id : false;
    }

    /**
     * Create product variations.
     *
     * @param int   $product_id      The product ID.
     * @param int   $shop_id         The shop ID.
     * @param array $product_details The product details.
     * @return void
     */
    private function createProductVariations($product_id, $shop_id, $product_details) {
        $product = wc_get_product($product_id);
        
        if (!$product) {
            return;
        }
        
        // Delete existing variations
        $existing_variations = $product->get_children();
        
        foreach ($existing_variations as $variation_id) {
            wp_delete_post($variation_id, true);
        }
        
        // Create new variations
        foreach ($product_details['variants'] as $variant) {
            $variation_data = [
                'attributes' => [],
                'sku'        => 'PRINTIFY-' . $product_details['id'] . '-' . $variant['id'],
                'regular_price' => number_format((float) $variant['price'] / 100, 2),
                'status'     => $this->settings->getOption('product_status'),
                'meta_data'  => [
                    [
                        'key'   => '_printify_variant_id',
                        'value' => $variant['id'],
                    ],
                ],
            ];
            
            if (!empty($variant['options'])) {
                foreach ($variant['options'] as $option_name => $option_value) {
                    $variation_data['attributes']['pa_' . sanitize_title($option_name)] = sanitize_title($option_value);
                }
            }
            
            $variation_id = $product->create_variation($variation_data);
            
            if (is_wp_error($variation_id)) {
                $this->log('Error creating variation: ' . $variation_id->get_error_message());
            }
        }
    }

    /**
     * Render the product sync page.
     *
     * @return void
     */
    public function renderPage() {
        $shops = $this->api->getShops();
        
        if (is_wp_error($shops)) {
            $shops = [];
        }
        
        include WPWPS_PLUGIN_DIR . 'templates/admin/product-sync.php';
    }

    /**
     * Log a message.
     *
     * @param string $message The message to log.
     * @return void
     */
    private function log($message) {
        if (!$this->settings->getOption('log_enabled')) {
            return;
        }
        
        $log_dir = WPWPS_PLUGIN_DIR . 'logs';
        
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
        }
        
        $log_file = $log_dir . '/sync-' . date('Y-m-d') . '.log';
        $timestamp = date('Y-m-d H:i:s');
        
        file_put_contents(
            $log_file,
            "[{$timestamp}] {$message}" . PHP_EOL,
            FILE_APPEND
        );
    }
}
