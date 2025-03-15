<?php
declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Database\Migrations;

class CreateEmailTemplatesTable
{
    public function up(): void
    {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpwps_email_templates (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            template_key varchar(50) NOT NULL,
            name varchar(100) NOT NULL,
            subject text NOT NULL,
            body longtext NOT NULL,
            variables text,
            is_active tinyint(1)