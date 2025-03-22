<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Database;

use ApolloWeb\WPWooCommercePrintifySync\Email\Database\EmailQueueTable;

class Setup {
    /**
     * Initialize database setup.
     */
    public function init() {
        register_activation_hook(WPWPS_PLUGIN_FILE, [$this, 'createTables']);
    }

    /**
     * Create all required database tables.
     */
    public function createTables() {
        // Create email queue table
        EmailQueueTable::createTable();
        
        // Create any other required tables here
        
        // Update DB version
        update_option('wpwps_db_version', WPWPS_VERSION);
    }
}
