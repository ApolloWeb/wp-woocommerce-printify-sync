<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Utilities;

class Database
{
    public static function addIndex($table, $column)
    {
        global $wpdb;
        $indexName = $column . '_index';
        $wpdb->query("CREATE INDEX $indexName ON $table ($column)");
    }

    public static function removeIndex($table, $column)
    {
        global $wpdb;
        $indexName = $column . '_index';
        $wpdb->query("DROP INDEX $indexName ON $table");
    }
}