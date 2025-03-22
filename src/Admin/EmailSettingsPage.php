<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

class EmailSettingsPage {
    private $template_loader;
    private $queue_monitor;
    private $settings;

    public function init() {
        add_action('admin_menu', [$this, 'addSettingsPage']);
        add_action('admin_init', [$this, 'registerSettings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function addSettingsPage() {
        add_submenu_page(
            'wpwps-dashboard',
            __('Email Settings', 'wp-woocommerce-printify-sync'),
            __('Email Settings', 'wp-woocommerce-printify-sync'),
            'manage_options',
            'wpwps-email-settings',
            [$this, 'renderPage']
        );
    }

    public function registerSettings() {
        register_setting('wpwps_email_settings', 'wpwps_smtp_settings');
        register_setting('wpwps_email_settings', 'wpwps_pop3_settings');
        register_setting('wpwps_email_settings', 'wpwps_email_template_settings');
    }

    private function getQueueStatus() {
        return $this->queue_monitor->getQueueStats();
    }
}
