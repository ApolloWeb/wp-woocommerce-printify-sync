<?php
/**
 * Plugin deactivation handler.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync
 */

namespace ApolloWeb\WPWooCommercePrintifySync;

use ApolloWeb\WPWooCommercePrintifySync\Core\Scheduler;

/**
 * Class Deactivator
 */
class Deactivator {
    /**
     * Plugin deactivation logic
     *
     * @return void
     */
    public static function deactivate() {
        // Clear scheduled events
        Scheduler::clearEvents();
        
        // Trigger action for other components to hook into deactivation
        do_action('wpwps_deactivated');
    }
}
