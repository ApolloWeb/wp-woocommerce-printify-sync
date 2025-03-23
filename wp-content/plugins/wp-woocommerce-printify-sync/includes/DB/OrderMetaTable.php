<?php
namespace ApolloWeb\WPWooCommercePrintifySync\DB;

class OrderMetaTable {
    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'wpps_order_meta';
    }

    public function install(): void {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id bigint(20) unsigned NOT NULL auto_increment,
            order_id bigint(20) unsigned NOT NULL,
            printify_id varchar(32) NOT NULL,
            printify_status varchar(32) NOT NULL,
            shipping_provider varchar(64),
            tracking_number varchar(64),
            tracking_url text,
            sync_status varchar(32),
            last_synced datetime,
            PRIMARY KEY  (id),
            KEY order_id (order_id),
            KEY printify_id (printify_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function uninstall(): void {
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS {$this->table_name}");
    }
}
