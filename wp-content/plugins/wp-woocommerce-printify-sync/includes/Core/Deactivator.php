<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Core;

class Deactivator
{
    /**
     * Plugin deactivation
     *
     * @return void
     */
    public static function deactivate(): void
    {
        // Clean up scheduled events
        if (function_exists('as_unschedule_all_actions')) {
            as_unschedule_all_actions('wpwps_process_product_import_queue');
            as_unschedule_all_actions('wpwps_start_product_import');
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}
