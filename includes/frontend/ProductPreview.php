<?php
/**
 * Product Preview
 *
 * Adds Printify product preview functionality to product pages.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Frontend
 * @version 1.0.0
 * @author ApolloWeb
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Frontend;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class ProductPreview {
    /**
     * Singleton instance
     *
     * @var ProductPreview
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
     *
     * @return ProductPreview
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Constructor code
    }
    
    /**
     * Initialize
     */
    public function init() {
        add_action('woocommerce_single_product_summary', array($this, 'add_preview_button'), 35);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_footer', array($this, 'add_preview_modal'));
    }
    
    /**
     * Enqueue scripts
     */
    public function enqueue_scripts() {
        if (is_product()) {
            wp_enqueue_style('wpwprintifysync-product-preview', WPWPRINTIFYSYNC_PLUGIN_URL . 'assets/css/product-preview.css', array(), WPWPRINTIFYSYNC_VERSION);
            wp_enqueue_script('wpwprintifysync-product-preview', WPWPRINTIFYSYNC_PLUGIN_URL . 'assets/js/product-preview.js', array('jquery'), WPWPRINTIFYSYNC_VERSION, true);
            
            global $product;
            
            if (!$product) {
                return;
            }
            
            $printify_id = get_post_meta($product->get_id(), '_printify_product_id', true);
            $blueprint_id = get_post_meta($product->get_id(), '_printify_blueprint_id', true);
            
            if (!$printify_id || !$blueprint_id) {
                return;
            }
            
            wp_localize_script('wpwprintifysync-product-preview', 'wpwprintifysync_preview', array(
                'product_id' => $product->get_id(),
                'printify_id' => $printify_id,
                'blueprint_id' => $blueprint_id,
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wpwprintifysync-preview-nonce'),
                'i18n' => array(
                    'loading' => __('Loading preview...', 'wp-woocommerce-printify-sync'),
                    'error' => __('Error loading preview.', 'wp-woocommerce-printify-sync')
                )
            ));
        }
    }
    
    /**
     * Add preview button
     */
    public function add_preview_button() {
        global $product;
        
        if (!$product) {
            return;
        }
        
        $printify_id = get_post_meta($product->get_id(), '_printify_product_id', true);
        $blueprint_id = get_post_meta($product->get_id(), '_printify_blueprint_id', true);
        
        if (!$printify_id || !$blueprint_id) {
            return;
        }
        
        echo '<div class="printify-preview-button-wrapper">';
        echo '<button type="button" class="button printify-preview-button">' . esc_html__('View Product Preview', 'wp-woocommerce-printify-sync') . '</button>';
        echo '</div>';
    }
    
    /**
     * Add preview modal
     */
    public function add_preview_modal() {
        if (is_product()) {
            ?>
            <div class="printify-preview-modal" style="display:none;">
                <div class="printify-preview-modal-content">
                    <span class="printify-preview-close">&times;</span>
                    <div class="printify-preview-header">
                        <h2><?php esc_html_e('Product Preview', 'wp-woocommerce-printify-sync'); ?></h2>
                    </div>
                    <div class="printify-preview-body">
                        <div class="printify-preview-loading">
                            <div class="printify-preview-spinner"></div>
                            <p><?php esc_html_e('Loading preview...', 'wp-woocommerce-printify-sync'); ?></p>
                        </div>
                        <div class="printify-preview-content"></div>
                    </div>
                </div>
            </div>
            <?php
        }
    }
}