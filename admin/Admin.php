/**
 * Admin class for Printify Sync plugin
 *
 * Author: Rob Owen
 *
 * Date: 2025-02-28
 *
 * @package ApolloWeb\WooCommercePrintifySync
 */
<?php

namespace ApolloWeb\WooCommercePrintifySync;

class Admin
{
    private $option_api_key = 'printify_api_key';
    
    private $option_shop_id = 'printify_selected_shop';
    
    private $api = null;

    public function __construct()
    {
        // Admin menu and settings
        add_action('admin_menu', [ $this, 'addSettingsPage' ]);
        add_action('admin_init', [ $this, 'registerSettings' ]);
        add_action('admin_enqueue_scripts', [ $this, 'enqueueAdminScripts' ]);
        
        // AJAX handlers
        add_action('wp_ajax_fetch_printify_shops', [ $this, 'fetchPrintifyShops' ]);
        add_action('wp_ajax_fetch_printify_products', [ $this, 'fetchPrintifyProducts' ]);
        add_action('wp_ajax_save_selected_shop', [ $this, 'saveSelectedShop' ]);
    }

    public function addSettingsPage()
    {
        add_options_page(
            __('Printify Sync Settings', 'wp-woocommerce-printify-sync'), 
            __('Printify Sync', 'wp-woocommerce-printify-sync'), 
            'manage_options', 
            'printify-sync', 
            [ $this, 'renderSettingsPage' ]
        );
    }

    public function renderSettingsPage()
    {
        echo '<div class="wrap"><h1>Printify Sync Settings</h1></div>';
    }
}
