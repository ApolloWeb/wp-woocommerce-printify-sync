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
        // Clean up if needed
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}
