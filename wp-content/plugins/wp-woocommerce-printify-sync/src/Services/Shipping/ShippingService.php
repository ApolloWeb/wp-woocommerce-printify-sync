<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services\Shipping;

use ApolloWeb\WPWooCommercePrintifySync\Traits\TimeStampTrait;

class ShippingService
{
    use TimeStampTrait;

    private PrintifyAPIClient $printifyClient;
    private LoggerInterface $logger;

    public function __construct(PrintifyAPIClient $printifyClient, LoggerInterface $logger)
    {
        $this->printifyClient = $printifyClient;
        $this->logger = $logger;
    }

    public function updateShippingStatus(int $orderId, array $trackingData): void
    {
        try {
            $order = wc_get_order($orderId);
            if (!$order) {
                throw new \Exception('Order not found');
            }

            $order->update_meta_data('_printify_tracking_number', $trackingData['tracking_number']);
            $order->update_meta_data('_printify_carrier', $trackingData['carrier']);
            $order->update_meta_data('_printify_shipping_status', $trackingData['status']);

            $note = sprintf(
                'Printify shipping update: %s - Tracking: %s (%s)',
                $trackingData['status'],
                $trackingData['tracking_number'],
                $trackingData['carrier']
            );

            $order->add_order_note($note);
            $order->save();

            if ($trackingData['status'] === 'shipped') {
                do_action('printify_order_shipped', $order);
            }

        } catch (\Exception $e) {
            $this->logger->error('Shipping status update failed', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
                'timestamp' => $this->getCurrentTime()
            ]);
            throw $e;
        }
    }

    public function getShippingEstimate(array $address): array
    {
        try {
            return $this->printifyClient->getShippingRates([
                'address' => $address
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Shipping estimate failed', [
                'error' => $e->getMessage(),
                'timestamp' => $this->getCurrentTime()
            ]);
            throw $e;
        }
    }
}