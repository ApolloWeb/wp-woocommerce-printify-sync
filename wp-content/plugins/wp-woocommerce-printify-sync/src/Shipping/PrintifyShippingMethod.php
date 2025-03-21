<?php
/**
 * Printify Shipping Method.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Shipping
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Shipping;

/**
 * Printify Shipping Method class.
 */
class PrintifyShippingMethod extends \WC_Shipping_Method
{
    /**
     * API client instance.
     *
     * @var null
     */
    private $api_client = null;

    /**
     * Logger instance.
     *
     * @var null
     */
    private $logger = null;

    /**
     * Shipping service instance.
     *
     * @var null
     */
    private $shipping_service = null;

    /**
     * Constructor.
     *
     * @param int $instance_id Instance ID.
     */
    public function __construct($instance_id = 0)
    {
        $this->id = 'printify_shipping';
        $this->instance_id = absint($instance_id);
        $this->method_title = __('Printify Shipping', 'wp-woocommerce-printify-sync');
        $this->method_description = __('Dynamically get shipping rates from Printify based on product specifications.', 'wp-woocommerce-printify-sync');
        $this->supports = [
            'shipping-zones',
            'instance-settings',
            'instance-settings-modal',
        ];
        
        // Load settings
        $this->init_form_fields();
        $this->init_settings();
        
        // Define user set variables
        $this->enabled = $this->get_option('enabled');
        $this->title = $this->get_option('title');
        
        // Register hooks
        add_action('woocommerce_update_options_shipping_' . $this->id, [$this, 'process_admin_options']);
        
        // Get plugin services
        $this->init_services();
    }

    /**
     * Initialize services.
     *
     * @return void
     */
    private function init_services()
    {
        global $wpwps_container;
        
        // Check if the container is available from the main plugin
        if (isset($wpwps_container)) {
            // Get services from the container
            if ($wpwps_container->has('api_client')) {
                $this->api_client = $wpwps_container->get('api_client');
            }
            if ($wpwps_container->has('logger')) {
                $this->logger = $wpwps_container->get('logger');
            }
            if ($wpwps_container->has('shipping_service')) {
                $this->shipping_service = $wpwps_container->get('shipping_service');
            }
        }
        
        // If services not available, create instances
        if (!$this->logger) {
            $logger_class = 'ApolloWeb\WPWooCommercePrintifySync\Services\Logger';
            if (class_exists($logger_class)) {
                $this->logger = new $logger_class();
            }
        }
        
        if (!$this->api_client && $this->logger) {
            $api_client_class = 'ApolloWeb\WPWooCommercePrintifySync\API\PrintifyAPIClient';
            $encryption_class = 'ApolloWeb\WPWooCommercePrintifySync\Services\EncryptionService';
            
            if (class_exists($api_client_class) && class_exists($encryption_class)) {
                $encryption = new $encryption_class();
                $this->api_client = new $api_client_class($this->logger, $encryption);
            }
        }
        
        if (!$this->shipping_service && $this->api_client && $this->logger) {
            $shipping_service_class = 'ApolloWeb\WPWooCommercePrintifySync\Services\ShippingService';
            
            if (class_exists($shipping_service_class)) {
                $this->shipping_service = new $shipping_service_class($this->api_client, $this->logger);
            }
        }
    }

    /**
     * Initialize form fields.
     *
     * @return void
     */
    public function init_form_fields()
    {
        $this->instance_form_fields = [
            'enabled' => [
                'title' => __('Enable/Disable', 'wp-woocommerce-printify-sync'),
                'type' => 'checkbox',
                'label' => __('Enable this shipping method', 'wp-woocommerce-printify-sync'),
                'default' => 'yes',
            ],
            'title' => [
                'title' => __('Method Title', 'wp-woocommerce-printify-sync'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'wp-woocommerce-printify-sync'),
                'default' => __('Printify Shipping', 'wp-woocommerce-printify-sync'),
                'desc_tip' => true,
            ],
            'fallback_rate' => [
                'title' => __('Fallback Rate', 'wp-woocommerce-printify-sync'),
                'type' => 'price',
                'description' => __('Fallback rate if Printify API is not available.', 'wp-woocommerce-printify-sync'),
                'default' => '5.99',
                'desc_tip' => true,
            ],
            'tax_status' => [
                'title' => __('Tax Status', 'wp-woocommerce-printify-sync'),
                'type' => 'select',
                'description' => __('Tax status of shipping method.', 'wp-woocommerce-printify-sync'),
                'default' => 'taxable',
                'options' => [
                    'taxable' => __('Taxable', 'wp-woocommerce-printify-sync'),
                    'none' => __('Not Taxable', 'wp-woocommerce-printify-sync'),
                ],
                'desc_tip' => true,
            ],
        ];
    }

    /**
     * Calculate shipping.
     *
     * @param array $package Package data.
     * @return void
     */
    public function calculate_shipping($package = [])
    {
        if ('yes' !== $this->enabled) {
            return;
        }
        
        // Check if we have the necessary services
        if (!$this->shipping_service || !$this->api_client) {
            $this->log('Shipping method services not available');
            $this->add_fallback_rate($package);
            return;
        }
        
        // Get shipping rates from Printify
        $rates = $this->shipping_service->getShippingRates($package);
        
        if (is_wp_error($rates)) {
            $this->log('Error getting shipping rates: ' . $rates->get_error_message());
            $this->add_fallback_rate($package);
            return;
        }
        
        if (empty($rates)) {
            $this->log('No shipping rates returned from Printify');
            $this->add_fallback_rate($package);
            return;
        }
        
        // Add shipping rates to WooCommerce
        foreach ($rates as $rate) {
            $this->add_rate([
                'id' => $this->get_rate_id($rate['id']),
                'label' => $rate['label'],
                'cost' => $rate['cost'],
                'calc_tax' => $rate['calc_tax'],
                'meta_data' => $rate['meta_data'] ?? [],
            ]);
        }
    }

    /**
     * Add fallback shipping rate.
     *
     * @param array $package Package data.
     * @return void
     */
    private function add_fallback_rate($package)
    {
        $fallback_rate = $this->get_option('fallback_rate', 5.99);
        
        $this->add_rate([
            'id' => $this->get_rate_id('fallback'),
            'label' => $this->title,
            'cost' => $fallback_rate,
            'calc_tax' => 'per_order',
        ]);
    }

    /**
     * Get rate ID.
     *
     * @param string $id Rate ID.
     * @return string Rate ID with instance ID.
     */
    private function get_rate_id($id)
    {
        return $this->id . ':' . $this->instance_id . '-' . $id;
    }

    /**
     * Log message.
     *
     * @param string $message Message to log.
     * @return void
     */
    private function log($message)
    {
        if ($this->logger) {
            $this->logger->info($message);
        }
    }
}
