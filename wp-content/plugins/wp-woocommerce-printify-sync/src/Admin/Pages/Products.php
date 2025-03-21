<?php
/**
 * Products page.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin\Pages
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Pages;

use ApolloWeb\WPWooCommercePrintifySync\API\PrintifyAPI;
use ApolloWeb\WPWooCommercePrintifySync\Helpers\TemplateRenderer;
use ApolloWeb\WPWooCommercePrintifySync\Helpers\Logger;

/**
 * Products admin page.
 */
class Products {
    /**
     * PrintifyAPI instance.
     *
     * @var PrintifyAPI
     */
    private $api;

    /**
     * Logger instance.
     *
     * @var Logger
     */
    private $logger;

    /**
     * TemplateRenderer instance.
     *
     * @var TemplateRenderer
     */
    private $template;

    /**
     * Constructor.
     *
     * @param PrintifyAPI      $api      PrintifyAPI instance.
     * @param TemplateRenderer $template TemplateRenderer instance.
     * @param Logger           $logger   Logger instance.
     */
    public function __construct(PrintifyAPI $api, TemplateRenderer $template, Logger $logger) {
        $this->api = $api;
        $this->template = $template;
        $this->logger = $logger;
    }

    /**
     * Initialize products admin page.
     *
     * @return void
     */
    public function init() {
        add_action('wp_ajax_wpwps_get_local_products', [$this, 'getProducts']);
    }

    /**
     * Render products admin page.
     *
     * @return void
     */
    public function render() {
        // Get shop details
        $settings = get_option('wpwps_settings', []);
        $shop_id = isset($settings['shop_id']) ? $settings['shop_id'] : '';
        $shop_name = isset($settings['shop_name']) ? $settings['shop_name'] : '';
        
        // Get recent products
        global $wpdb;
        $recent_products = $wpdb->get_results("
            SELECT 
                p.ID as product_id,
                p.post_title as title,
                p.post_status as status,
                pm.meta_value as printify_id
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_printify_product_id'
            WHERE p.post_type = 'product'
            ORDER BY p.post_date DESC
            LIMIT 10
        ");
        
        // Get stats
        $stats = $this->getProductStats();
        
        // Render template
        $this->template->render('products', [
            'shop_id' => $shop_id,
            'shop_name' => $shop_name,
            'recent_products' => $recent_products,
            'stats' => $stats,
            'nonce' => wp_create_nonce('wpwps_products_nonce'),
        ]);
    }

    /**
     * Get products via AJAX.
     *
     * @return void
     */
    public function getProducts() {
        check_ajax_referer('wpwps_products_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'wp-woocommerce-printify-sync')]);
        }

        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 20;
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';

        // Build query.
        global $wpdb;
        $where = ["p.post_type = 'product'"];

        if ($status) {
            $where[] = $wpdb->prepare("p.post_status = %s", 'wc-' . $status);
        }

        if ($search) {
            $where[] = $wpdb->prepare(
                "(p.ID LIKE %s OR p.post_title LIKE %s)",
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%'
            );
        }

        $where = implode(' AND ', $where);

        // Get total count.
        $total = $wpdb->get_var("
            SELECT COUNT(DISTINCT p.ID)
            FROM {$wpdb->posts} p
            WHERE {$where}
        ");

        // Get products.
        $offset = ($page - 1) * $per_page;
        $products = $wpdb->get_results($wpdb->prepare("
            SELECT 
                p.ID as product_id,
                p.post_title as title,
                p.post_status as status,
                pm.meta_value as printify_id
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_printify_product_id'
            WHERE {$where}
            GROUP BY p.ID
            ORDER BY p.post_date DESC
            LIMIT %d OFFSET %d",
            $per_page,
            $offset
        ));

        wp_send_json_success([
            'products' => $products,
            'total' => $total,
            'pages' => ceil($total / $per_page),
        ]);
    }

    /**
     * Get product statistics.
     *
     * @return array
     */
    private function getProductStats() {
        global $wpdb;
        
        // Count Printify products
        $printify_count = $wpdb->get_var(
            "SELECT COUNT(DISTINCT post_id) 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_printify_product_id' 
            AND post_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = 'product')"
        );
        
        // Count total products
        $total_count = $wpdb->get_var(
            "SELECT COUNT(ID) 
            FROM {$wpdb->posts} 
            WHERE post_type = 'product' AND post_status IN ('publish', 'draft')"
        );
        
        // Count published products
        $published_count = $wpdb->get_var(
            "SELECT COUNT(ID) 
            FROM {$wpdb->posts} 
            WHERE post_type = 'product' AND post_status = 'publish'"
        );
        
        // Count draft products
        $draft_count = $wpdb->get_var(
            "SELECT COUNT(ID) 
            FROM {$wpdb->posts} 
            WHERE post_type = 'product' AND post_status = 'draft'"
        );
        
        return [
            'total' => $total_count ?: 0,
            'printify' => $printify_count ?: 0,
            'non_printify' => ($total_count - $printify_count) ?: 0,
            'published' => $published_count ?: 0,
            'draft' => $draft_count ?: 0,
        ];
    }
}
