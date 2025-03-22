// ...existing code...
    private function registerServices() {
        // ...existing code...
        
        // Register order ticket handler
        $this->container->register('order_ticket_handler', 'ApolloWeb\WPWooCommercePrintifySync\Orders\OrderTicketHandler')
            ->addArgument($this->container->get('ticket_service'))
            ->addArgument($this->container->get('chatgpt_client'))
            ->addArgument($this->container->get('logger'));
            
        // ...existing code...
    }

class Plugin {
    // ...existing code...

    public function init() {
        // Declare HPOS compatibility
        add_action('before_woocommerce_init', function() {
            if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
                \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
            }
        });
        // ...existing code...
    }
}
