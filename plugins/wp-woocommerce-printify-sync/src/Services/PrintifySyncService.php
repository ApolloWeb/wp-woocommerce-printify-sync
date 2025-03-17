<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use ApolloWeb\WPWooCommercePrintifySync\Interfaces\PrintifyClientInterface;
use ApolloWeb\WPWooCommercePrintifySync\Interfaces\LoggerInterface;

class PrintifySyncService
{
    private PrintifyClientInterface $printifyClient;
    private LoggerInterface $logger;

    public function __construct(PrintifyClientInterface $printifyClient, LoggerInterface $logger)
    {
        $this->printifyClient = $printifyClient;
        $this->logger = $logger;
    }

    public function syncToPrintify(\WC_Product $product): void
    {
        $printifyId = $product->get_meta('_printify_product_id');
        
        if (!$printifyId) {
            $this->logger->info('Product not synced with Printify, skipping', [
                'product_id' => $product->get_id()
            ]);
            return;
        }

        try {
            $data = $this->prepareProductData($product);
            $this->printifyClient->updateProduct($printifyId, $data);

            $this->logger->info('Product synced to Printify', [
                'product_id' => $product->get_id(),
                'printify_id' => $printifyId
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to sync to Printify', [
                'product_id' => $product->get_id(),
                'printify_id' => $printifyId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function prepareProductData(\WC_Product $product): array
    {
        return [
            'title' => $product->get_name(),
            'description' => $product->get_description(),
            'price' => $product->get_regular_price(),
            // Add other needed fields
        ];
    }
}