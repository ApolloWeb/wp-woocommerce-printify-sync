<?php
/**
 * Google Analytics Integration
 *
 * Integrates Google Analytics tracking with the plugin to track product and order events.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Analytics
 * @version 1.0.0
 * @author ApolloWeb
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Analytics;

use ApolloWeb\WPWooCommercePrintifySync\Logging\Logger;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class GoogleAnalyticsIntegration {
    /**
     * Singleton instance
     *
     * @var GoogleAnalyticsIntegration
     */
    private static $instance = null;
    
    /**
     * GA tracking ID
     *
     * @var string
     */
    private $tracking_id = '';
    
    /**
     * Is GA4 enabled
     *
     * @var bool
     */
    private $is_ga4 = true;
    
    /**
     * Is enhanced ecommerce enabled
     *
     * @var bool
     */
    private $enhanced_ecommerce = true;
    
    /**
     * Get singleton instance
     *
     * @return GoogleAnalyticsIntegration
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
        $settings = get_option('wpwprintifysync_settings', array());
        $this->tracking_id = $settings['ga_tracking_id'] ?? '';
        $this->is_ga4 = isset($settings['ga_version']) && $settings['ga_version'] === 'ga4';
        $this->enhanced_ecommerce = isset($settings['ga_enhanced_ecommerce']) && $settings['ga_enhanced_ecommerce'] === 'yes';
    }
    
    /**
     * Initialize
     */
    public function init() {
        if (empty($this->tracking_id)) {
            return;
        }
        
        // Add tracking code to frontend
        add_action('wp_head', array($this, 'add_tracking_code'));
        
        // Track Printify-related events
        add_action('wpwprintifysync_after_product_import', array($this, 'track_product_import'), 10, 2);
        add_action('wpwprintifysync_after_product_update', array($this, 'track_product_update'), 10, 2);
        add_action('wpwprintifysync_after_order_send', array($this, 'track_order_sent'), 10, 3);
        
        // Add admin settings
        add_filter('wpwprintifysync_settings_tabs', array($this, 'add_settings_tab'));
        add_filter('wpwprintifysync_settings_sections', array($this, 'add_settings_section'));
        add_filter('wpwprintifysync_settings_fields', array($this, 'add_settings_fields'));
        
        // Add event tracking to frontend
        if ($this->enhanced_ecommerce) {
            add_action('woocommerce_after_add_to_cart_button', array($this, 'print_add_to_cart_tracking'), 20);
            add_action('woocommerce_after_checkout_form', array($this, 'print_checkout_tracking'));
        }
    }
    
    /**
     * Add tracking code to frontend
     */
    public function add_tracking_code() {
        if (empty($this->tracking_id)) {
            return;
        }
        
        if ($this->is_ga4) {
            // GA4 tracking code
            ?>
            <!-- Global site tag (gtag.js) - Google Analytics -->
            <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr($this->tracking_id); ?>"></script>
            <script>
              window.dataLayer = window.dataLayer || [];
              function gtag(){dataLayer.push(arguments);}
              gtag('js', new Date());
              gtag('config', '<?php echo esc_attr($this->tracking_id); ?>', {
                'send_page_view': true,
                'custom_map': {
                  'dimension1': 'product_source',
                  'dimension2': 'printify_id'
                }
              });
            </script>
            <?php
            
            if ($this->enhanced_ecommerce) {
                ?>
                <script>
                gtag('set', 'developer_id.dNDMyYj', true);
                </script>
                <?php
            }
        } else {
            // Universal Analytics tracking code
            ?>
            <!-- Google Analytics -->
            <script>
            (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
            (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
            m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
            })(window,document,'script','https://www.google-analytics.com/analytics.js','ga');
            
            ga('create', '<?php echo esc_attr($this->tracking_id); ?>', 'auto');
            ga('send', 'pageview');
            </script>
            <?php
            
            if ($this->enhanced_ecommerce) {
                ?>
                <script>
                ga('require', 'ec');
                </script>
                <?php
            }
        }
    }
    
    /**
     * Track product import event
     *
     * @param int $wc_product_id WooCommerce product ID
     * @param array $product_data Printify product data
     */
    public function track_product_import($wc_product_id, $product_data) {
        $event_data = array(
            'event_category' => 'Printify',
            'event_label' => $product_data['title'] ?? 'Unknown Product',
            'value' => 1
        );
        
        $this->track_event('product_import', $event_data);
        
        // Log the tracking
        Logger::get_instance()->debug('Google Analytics: Tracked product import', array(
            'wc_product_id' => $wc_product_id,
            'printify_id' => $product_data['id'] ?? 'unknown',
            'tracking_id' => $this->tracking_id
        ));
    }
    
    /**
     * Track product update event
     *
     * @param int $wc_product_id WooCommerce product ID
     * @param array $product_data Printify product data
     */
    public function track_product_update($wc_product_id, $product_data) {
        $event_data = array(
            'event_category' => 'Printify',
            'event_label' => $product_data['title'] ?? 'Unknown Product',
            'value' => 1
        );
        
        $this->track_event('product_update', $event_data);
        
        // Log the tracking
        Logger::get_instance()->debug('Google Analytics: Tracked product update', array(
            'wc_product_id' => $wc_product_id,
            'printify_id' => $product_data['id'] ?? 'unknown',
            'tracking_id' => $this->tracking_id
        ));
    }
    
    /**
     * Track order sent event
     *
     * @param int $order_id WooCommerce order ID
     * @param string $printify_order_id Printify order ID
     * @param array $response API response
     */
    public function track_order_sent($order_id, $printify_order_id, $response) {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return;
        }
        
        $event_data = array(
            'event_category' => 'Printify',
            'event_label' => 'Order #' . $order->get_order_number(),
            'value' => $order->get_total()
        );
        
        $this->track_event('order_sent', $event_data);
        
        // Track for enhanced ecommerce
        if ($this->enhanced_ecommerce) {
            $this->track_enhanced_purchase($order, $printify_order_id);
        }
        
        // Log the tracking
        Logger::get_instance()->debug('Google Analytics: Tracked order sent', array(
            'order_id' => $order_id,
            'printify_order_id' => $printify_order_id,
            'tracking_id' => $this->tracking_id
        ));
    }
    
    /**
     * Track enhanced ecommerce purchase
     *
     * @param WC_Order $order WooCommerce order
     * @param string $printify_order_id Printify order ID
     */
    private function track_enhanced_purchase($order, $printify_order_id) {
        $items = $order->get_items();
        $purchase_data = array(
            'transaction_id' => $order->get_order_number(),
            'affiliation' => get_bloginfo('name'),
            'value' => $order->get_total(),
            'tax' => $order->get_total_tax(),
            'shipping' => $order->get_shipping_total(),
            'currency' => $order->get_currency(),
            'items' => array()
        );
        
        $position = 1;
        
        foreach ($items as $item) {
            $product = $item->get_product();
            
            if (!$product) {
                continue;
            }
            
            $product_id = $product->get_id();
            $printify_id = get_post_meta($product_id, '_printify_product_id', true);
            $category = '';
            
            $terms = get_the_terms($product_id, 'product_cat');
            if (!empty($terms) && !is_wp_error($terms)) {
                $category = $terms[0]->name;
            }
            
            $purchase_data['items'][] = array(
                'id' => $product->get_sku() ? $product->get_sku() : $product_id,
                'name' => $item->get_name(),
                'category' => $category,
                'price' => $order->get_item_total($item),
                'quantity' => $item->get_quantity(),
                'position' => $position,
                'product_source' => $printify_id ? 'printify' : 'other',
                'printify_id' => $printify_id ?: ''
            );
            
            $position++;
        }
        
        // Record the purchase event
        if ($this->is_ga4) {
            $this->record_ga4_purchase($purchase_data);
        } else {
            $this->record_ua_purchase($purchase_data);
        }
    }
    
    /**
     * Record GA4 purchase event
     *
     * @param array $purchase_data Purchase data
     */
    private function record_ga4_purchase($purchase_data) {
        // This would be implemented via dataLayer or server-to-server API
        // For now, we'll just log the event
        Logger::get_instance()->debug('Google Analytics 4: Purchase tracked', $purchase_data);
    }
    
    /**
     * Record Universal Analytics purchase event
     *
     * @param array $purchase_data Purchase data
     */
    private function record_ua_purchase($purchase_data) {
        // This would be implemented via measurement protocol
        // For now, we'll just log the event
        Logger::get_instance()->debug('Universal Analytics: Purchase tracked', $purchase_data);
    }
    
    /**
     * Print add to cart tracking code
     */
    public function print_add_to_cart_tracking() {
        global $product;
        
        if (!$product) {
            return;
        }
        
        $printify_id = get_post_meta($product->get_id(), '_printify_product_id', true);
        $category = '';
        
        $terms = get_the_terms($product->get_id(), 'product_cat');
        if (!empty($terms) && !is_wp_error($terms)) {
            $category = $terms[0]->name;
        }
        
        if ($this->is_ga4) {
            ?>
            <script>
            jQuery(document).ready(function($) {
                $('.single_add_to_cart_button').click(function(e) {
                    if (!$(this).hasClass('disabled')) {
                        gtag('event', 'add_to_cart', {
                            items: [{
                                item_id: '<?php echo esc_js($product->get_sku() ? $product->get_sku() : $product->get_id()); ?>',
                                item_name: '<?php echo esc_js($product->get_name()); ?>',
                                item_category: '<?php echo esc_js($category); ?>',
                                price: <?php echo esc_js($product->get_price()); ?>,
                                quantity: $('input[name="quantity"]').val() || 1,
                                product_source: '<?php echo $printify_id ? 'printify' : 'other'; ?>',
                                printify_id: '<?php echo esc_js($printify_id ?: ''); ?>'
                            }],
                            currency: '<?php echo esc_js(get_woocommerce_currency()); ?>'
                        });
                    }
                });
            });
            </script>
            <?php
        } else {
            ?>
            <script>
            jQuery(document).ready(function($) {
                $('.single_add_to_cart_button').click(function(e) {
                    if (!$(this).hasClass('disabled')) {
                        ga('ec:addProduct', {
                            'id': '<?php echo esc_js($product->get_sku() ? $product->get_sku() : $product->get_id()); ?>',
                            'name': '<?php echo esc_js($product->get_name()); ?>',
                            'category': '<?php echo esc_js($category); ?>',
                            'price': '<?php echo esc_js($product->get_price()); ?>',
                            'quantity': $('input[name="quantity"]').val() || 1,
                            'dimension1': '<?php echo $printify_id ? 'printify' : 'other'; ?>',
                            'dimension2': '<?php echo esc_js($printify_id ?: ''); ?>'
                        });
                        ga('ec:setAction', 'add');
                        ga('send', 'event', 'Printify', 'Add to Cart', '<?php echo esc_js($product->get_name()); ?>');
                    }
                });
            });
            </script>
            <?php
        }
    }
    
    /**
     * Print checkout tracking code
     */
    public function print_checkout_tracking() {
        global $woocommerce;
        
        if (!$woocommerce->cart) {
            return;
        }
        
        $cart_items = $woocommerce->cart->get_cart();
        $position = 1;
        $items = array();
        
        foreach ($cart_items as $cart_item) {
            $product =