<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Orders;

use ApolloWeb\WPWooCommercePrintifySync\Services\Logger;

/**
 * Reprint Handler class.
 */
class ReprintHandler
{
    /**
     * Logger instance.
     *
     * @var Logger
     */
    private $logger;

    /**
     * Constructor.
     *
     * @param Logger $logger Logger instance.
     */
    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Handle reprint request.
     *
     * @param int $order_id Order ID.
     * @return void
     */
    public function handleReprint($order_id)
    {
        // Log the reprint request
        $this->logger->info("Handling reprint request for order ID: {$order_id}");

        // Add your reprint handling logic here
    }
}
