<?php

namespace ApolloWeb\WPWooCommercePrintifySync;

class Enqueue
{
    public function __construct()
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminScripts']);
    }

    public function enqueueAdminScripts($hook_suffix)
    {
        if ($hook_suffix === 'woocommerce_page_printify-shop-selection' || $hook_suffix === 'woocommerce_page_printify-sync-settings') {
            wp_enqueue_script('printify-sync-admin', plugin_dir_url(__FILE__) . 'assets/js/admin.js', ['jquery'], '1.0.0', true);
            wp_localize_script('printify-sync-admin', 'printifySync', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('printify_sync_nonce'),
            ]);
        }
    }
}

new Enqueue();