<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class QueueManager
{
    public const IMPORT_PRODUCTS_GROUP = 'wpwps_import_products';
    public const UPDATE_PRODUCT_GROUP = 'wpwps_update_product';
    public const CHUNK_SIZE = 10;

    private string $currentTime;
    private string $currentUser;

    public function __construct(string $currentTime = '2025-03-15 21:18:13', string $currentUser = 'ApolloWeb')
    {
        $this->currentTime = $currentTime;
        $this->currentUser = $currentUser;
    }

    public function scheduleProductImport(array $printifyIds): void
    {
        $chunks = array_chunk($printifyIds, self::CHUNK_SIZE);
        
        foreach ($chunks as $index => $chunk) {
            as_schedule_single_action(
                strtotime("+{$index} minutes", strtotime($this->currentTime)),
                'wpwps_process_product_chunk',
                [
                    'products' => $chunk,
                    'context' => 'import',
                    'user' => $this->currentUser,
                    'timestamp' => $this->currentTime
                ],
                self::IMPORT_PRODUCTS_GROUP
            );
        }

        as_schedule_single_action(
            strtotime('+1 hour', strtotime($this->currentTime)),
            'wpwps_import_cleanup',
            ['timestamp' => $this->currentTime],
            self::IMPORT_PRODUCTS_GROUP
        );
    }

    public function scheduleProductUpdate(string $printifyId): void
    {
        as_schedule_single_action(
            strtotime($this->currentTime),
            'wpwps_process_product_update',
            [
                'printify_id' => $printifyId,
                'context' => 'webhook',
                'user' => $this->currentUser,
                'timestamp' => $this->currentTime
            ],
            self::UPDATE_PRODUCT_GROUP
        );
    }
}