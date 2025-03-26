<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Pages;

use ApolloWeb\WPWooCommercePrintifySync\Services\BladeTemplateEngine;
use ApolloWeb\WPWooCommercePrintifySync\Services\PrintifyAPI;

class Shipping {
    private $template;
    private $printifyAPI;

    public function __construct(BladeTemplateEngine $template, PrintifyAPI $printifyAPI) {
        $this->template = $template;
        $this->printifyAPI = $printifyAPI;

        // Enqueue assets
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function render(): void {
        $data = [];
        $this->template->render('wpwps-shipping', $data);
    }

    public function enqueueAssets(string $hook): void {
        if ($hook !== 'printify-sync_page_wpwps-shipping') {
            return;
        }

        // Enqueue shared assets
        wp_enqueue_style('google-fonts-inter');
        wp_enqueue_style('bootstrap');
        wp_enqueue_script('bootstrap');
        wp_enqueue_style('font-awesome');
        wp_enqueue_script('wpwps-toast');

        // Our custom page assets
        wp_enqueue_style(
            'wpwps-shipping',
            WPWPS_URL . 'assets/css/wpwps-shipping.css',
            [],
            WPWPS_VERSION
        );
        wp_enqueue_script(
            'wpwps-shipping',
            WPWPS_URL . 'assets/js/wpwps-shipping.js',
            ['jquery', 'bootstrap', 'wpwps-toast'],
            WPWPS_VERSION,
            true
        );
    }
}