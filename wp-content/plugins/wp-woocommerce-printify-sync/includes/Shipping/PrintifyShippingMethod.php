<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Shipping;

use ApolloWeb\WPWooCommercePrintifySync\Repositories\ShippingRepository;

/**
 * Printify Shipping Method
 */
class PrintifyShippingMethod extends \WC_Shipping_Method {
    /**
     * @var ShippingRepository
     */
    private $repository;
    
    /**
     * Constructor
     *
     * @param int $instance_id Instance ID
     */
    public function __construct($instance_id = 0) {
        $this->id = 'printify';
        $this->instance_id = absint($instance_id);
        $this->method_title = __('Printify Shipping', 'wp-woocommerce-printify-sync');
        $this->method_description = __('Ship products directly from Printify print providers.', 'wp-woocommerce-printify-sync');
        $this->supports = [
            'shipping-zones',
            'instance-settings',
        ];
        
        $this->repository = new ShippingRepository();
        
        $this->init();
    }
    
    /**
     * Initialize settings
     */
    private function init(): void {
        // Load settings
        $this->init_form_fields();
        $this->init_settings();
        
        // Define settings
        $this->title = $this->get_option('title', $this->method_title);
        $this->tax_status = $this->get_option('tax_status', 'taxable');
        $this->cost_adjustment = (float) $this->get_option('cost_adjustment', '0');
        $this->adjustment_type = $this->get_option('adjustment_type', 'fixed');
        
        // Save settings
        add_action('woocommerce_update_options_shipping_' . $this->id, [$this, 'process_admin_options']);
    }
    
    /**
     * Initialize form fields
     */
    public function init_form_fields(): void {
        $this->instance_form_fields = [
            'title' => [
                'title' => __('Method Title', 'wp-woocommerce-printify-sync'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'wp-woocommerce-printify-sync'),
                'default' => $this->method_title,
                'desc_tip' => true,
            ],
            'tax_status' => [
                'title' => __('Tax Status', 'wp-woocommerce-printify-sync'),
                'type' => 'select',
                'description' => __('Determines whether or not taxes are applied to shipping costs.', 'wp-woocommerce-printify-sync'),
                'default' => 'taxable',
                'options' => [
                    'taxable' => __('Taxable', 'wp-woocommerce-printify-sync'),
                    'none' => __('Not Taxable', 'wp-woocommerce-printify-sync'),
                ],
                'desc_tip' => true,
            ],
            'cost_adjustment' => [
                'title' => __('Cost Adjustment', 'wp-woocommerce-printify-sync'),
                'type' => 'text',
                'description' => __('Adjust Printify shipping costs by a fixed amount or percentage.', 'wp-woocommerce-printify-sync'),
                'default' => '0',
                'desc_tip' => true,
            ],
            'adjustment_type' => [
                'title' => __('Adjustment Type', 'wp-woocommerce-printify-sync'),
                'type' => 'select',
                'description' => __('Determine how to apply the cost adjustment.', 'wp-woocommerce-printify-sync'),
                'default' => 'fixed',
                'options' => [
                    'fixed' => __('Fixed Amount', 'wp-woocommerce-printify-sync'),
                    'percentage' => __('Percentage', 'wp-woocommerce-printify-sync'),
                ],
                'desc_tip' => true,
            ],
        ];
    }
    
    /**
     * Calculate shipping
     *
     * @param array $package Package data
     */
    public function calculate_shipping($package = []): void {
        if (empty($package['destination']['country'])) {
            return;
        }
        
        // Check if there are any Printify products
        $has_printify_products = false;
        
        foreach ($package['contents'] as $item) {
            $product = $item['data'];
            $product_id = $product->is_type('variation') ? $product->get_parent_id() : $product->get_id();
            
            $printify_id = get_post_meta($product_id, '_printify_product_id', true);
            
            if (!empty($printify_id)) {
                $has_printify_products = true;
                break;
            }
        }
        
        if (!$has_printify_products) {
            return;
        }
        
        // Add shipping rate
        $this->add_rate([
            'id' => $this->get_rate_id(),
            'label' => $this->title,
            'cost' => 0, // Cost will be calculated in adjustPackageRates filter
            'package' => $package,
        ]);
    }
    
    /**
     * Apply cost adjustment
     *
     * @param float $cost Original cost
     * @return float Adjusted cost
     */
    public function apply_cost_adjustment(float $cost): float {
        $adjustment = (float) $this->cost_adjustment;
        
        if ($adjustment === 0) {
            return $cost;
        }
        
        if ($this->adjustment_type === 'percentage') {
            return $cost * (1 + ($adjustment / 100));
        }
        
        return $cost + $adjustment;
    }
}
