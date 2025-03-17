<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use Automattic\WooCommerce\Utilities\OrderUtil;

class OrderTrackingHandler
{
    private ConfigService $config;
    private PrintifyAPI $printifyApi;
    private LoggerInterface $logger;

    public function __construct(
        ConfigService $config,
        PrintifyAPI $printifyApi,
        LoggerInterface $logger
    ) {
        $this->config = $config;
        $this->printifyApi = $printifyApi;
        $this->logger = $logger;
        
        $this->initHooks();
    }

    private function initHooks(): void
    {
        add_action('wpwps_update_tracking', [$this, 'updateOrderTracking']);
        add_action('woocommerce_order_details_after_order_table', [$this, 'displayTrackingInfo']);
        add_filter('woocommerce_order_tracking_status', [$this, 'modifyTrackingStatus'], 10, 2);
    }

    public function updateOrderTracking(int $orderId): void
    {
        try {
            $order = wc_get_order($orderId);
            if (!$order) {
                throw new \Exception("Order not found: {$orderId}");
            }

            $printifyOrderId = $order->get_meta('_printify_order_id');
            if (!$printifyOrderId) {
                return;
            }

            $trackingInfo = $this->printifyApi->getOrderTracking($printifyOrderId);
            
            $this->updateOrderTrackingMeta($order, $trackingInfo);

            // Update order status based on tracking
            $this->updateOrderStatusFromTracking($order, $trackingInfo);

            $this->logger->info('Order tracking updated', [
                'order_id' => $orderId,
                'tracking_number' => $trackingInfo['tracking_number'] ?? null,
                'carrier' => $trackingInfo['carrier'] ?? null
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to update order tracking', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function updateOrderTrackingMeta(\WC_Order $order, array $trackingInfo): void
    {
        $order->update_meta_data('_printify_tracking_number', $trackingInfo['tracking_number'] ?? '');
        $order->update_meta_data('_printify_tracking_carrier', $trackingInfo['carrier'] ?? '');
        $order->update_meta_data('_printify_tracking_url', $trackingInfo['tracking_url'] ?? '');
        $order->update_meta_data('_printify_tracking_status', $trackingInfo['status'] ?? '');
        $order->update_meta_data('_printify_tracking_updated', current_time('mysql', true));
        $order->save();
    }

    private function updateOrderStatusFromTracking(\WC_Order $order, array $trackingInfo): void
    {
        $status = $trackingInfo['status'] ?? '';
        
        switch ($status) {
            case 'shipped':
                if ($order->get_status() === 'processing') {
                    $order->update_status('completed', 'Order shipped via ' . ($trackingInfo['carrier'] ?? 'carrier'));
                }
                break;
            
            case 'delivered':
                if ($order->get_status() !== 'completed') {
                    $order->update_status('completed', 'Order delivered');
                }
                break;
        }
    }

    public function displayTrackingInfo(\WC_Order $order): void
    {
        $trackingNumber = $order->get_meta('_printify_tracking_number');
        if (!$trackingNumber) {
            return;
        }

        $carrier = $order->get_meta('_printify_tracking_carrier');
        $trackingUrl = $order->get_meta('_printify_tracking_url');
        $status = $order->get_meta('_printify_tracking_status');
        
        ?>
        <h2>Tracking Information</h2>
        <table class="woocommerce-table tracking-info">
            <tbody>
                <tr>
                    <th>Carrier:</th>
                    <td><?php echo esc_html($carrier); ?></td>
                </tr>
                <tr>
                    <th>Tracking Number:</th>
                    <td>
                        <?php if ($trackingUrl): ?>
                            <a href="<?php echo esc_url($trackingUrl); ?>" target="_blank">
                                <?php echo esc_html($trackingNumber); ?>
                            </a>
                        <?php else: ?>
                            <?php echo esc_html($trackingNumber); ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php if ($status): ?>
                    <tr>
                        <th>Status:</th>
                        <td><?php echo esc_html(ucfirst($status)); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
    }
}