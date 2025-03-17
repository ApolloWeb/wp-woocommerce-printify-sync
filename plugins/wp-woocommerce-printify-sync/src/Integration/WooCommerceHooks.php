<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Integration;

use ApolloWeb\WPWooCommercePrintifySync\Services\OrderSync\OrderCreationService;

class WooCommerceHooks
{
    private OrderCreationService $orderCreation;
    private LoggerInterface $logger;

    public function __construct(
        OrderCreationService $orderCreation,
        LoggerInterface $logger
    ) {
        $this->orderCreation = $orderCreation;
        $this->logger = $logger;
        $this->initHooks();
    }

    private function initHooks(): void
    {
        // Order creation
        add_action('woocommerce_checkout_order_processed', [$this, 'handleNewOrder'], 10, 3);
        add_action('woocommerce_order_status_changed', [$this, 'handleOrderStatusChange'], 10, 4);
        
        // Payment completion
        add_action('woocommerce_payment_complete', [$this, 'handlePaymentComplete']);
        
        // Order status display
        add_action('woocommerce_admin_order_data_after_order_details', [$this, 'displayPrintifyStatus']);
        
        // Order processing
        add_filter('woocommerce_payment_complete_order_status', [$this, 'modifyOrderStatus'], 10, 3);
    }

    public function handleNewOrder(int $orderId, array $postedData, \WC_Order $order): void
    {
        try {
            // Check if order has Printify products
            $hasPrintifyProducts = false;
            foreach ($order->get_items() as $item) {
                $product = $item->get_product();
                if ($product && $product->get_meta('_printify_product_id')) {
                    $hasPrintifyProducts = true;
                    break;
                }
            }

            if ($hasPrintifyProducts) {
                $order->update_meta_data('_has_printify_products', 'yes');
                $order->save();
            }

        } catch (\Exception $e) {
            $this->logger->error('Failed to process new order', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function handleOrderStatusChange(
        int $orderId,
        string $oldStatus,
        string $newStatus,
        \WC_Order $order
    ): void {
        try {
            if ($order->get_meta('_has_printify_products') !== 'yes') {
                return;
            }

            // Create Printify order when payment is completed
            if ($newStatus === 'processing' && !$order->get_meta('_printify_order_id')) {
                $this->orderCreation->createPrintifyOrder($order);
            }

        } catch (\Exception $e) {
            $this->logger->error('Failed to handle order status change', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function handlePaymentComplete(int $orderId): void
    {
        $order = wc_get_order($orderId);
        if (!$order || $order->get_meta('_has_printify_products') !== 'yes') {
            return;
        }

        if (!$order->get_meta('_printify_order_id')) {
            $this->orderCreation->createPrintifyOrder($order);
        }
    }

    public function displayPrintifyStatus(\WC_Order $order): void
    {
        $printifyOrderId = $order->get_meta('_printify_order_id');
        if (!$printifyOrderId) {
            return;
        }

        $status = $order->get_meta('_printify_order_status');
        $lastUpdate = $order->get_meta('_printify_status_updated');

        ?>
        <div class="printify-order-status">
            <h3><?php esc_html_e('Printify Status', 'wp-woocommerce-printify-sync'); ?></h3>
            <p>
                <strong><?php esc_html_e('Order ID:', 'wp-woocommerce-printify-sync'); ?></strong>
                <?php echo esc_html($printifyOrderId); ?>
            </p>
            <p>
                <strong><?php esc_html_e('Status:', 'wp-woocommerce-printify-sync'); ?></strong>
                <span class="status-<?php echo esc_attr($status); ?>">
                    <?php echo esc_html(ucfirst($status)); ?>
                </span>
            </p>
            <?php if ($lastUpdate): ?>
                <p>
                    <strong><?php esc_html_e('Last Updated:', 'wp-woocommerce-printify-sync'); ?></strong>
                    <?php echo esc_html($lastUpdate); ?>
                </p>
            <?php endif; ?>
        </div>
        <?php
    }

    public function modifyOrderStatus(string $status, int $orderId, \WC_Order $order): string
    {
        if ($order->get_meta('_has_printify_products') === 'yes') {
            return 'processing';
        }
        return $status;
    }
}