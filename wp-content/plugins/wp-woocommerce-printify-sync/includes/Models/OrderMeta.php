<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Models;

class OrderMeta {
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'wpps_order_meta';
    }

    public function updateOrderMeta(int $order_id, array $data): void {
        global $wpdb;

        $existing = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id FROM {$this->table_name} WHERE order_id = %d",
                $order_id
            )
        );

        if ($existing) {
            $wpdb->update(
                $this->table_name,
                array_merge($data, ['last_synced' => current_time('mysql')]),
                ['order_id' => $order_id]
            );
        } else {
            $wpdb->insert(
                $this->table_name,
                array_merge(
                    ['order_id' => $order_id],
                    $data,
                    ['last_synced' => current_time('mysql')]
                )
            );
        }
    }
}
