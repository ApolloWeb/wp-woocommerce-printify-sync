<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Pages;

use ApolloWeb\WPWooCommercePrintifySync\Core\ServiceContainer;

class ShippingSettingsPage extends AbstractAdminPage
{
    public function __construct($templateEngine, ServiceContainer $container = null)
    {
        parent::__construct($templateEngine, $container);
        $this->slug = 'wpwps-shipping-settings';
        $this->pageTitle = 'Shipping Settings';
        $this->menuTitle = 'Shipping';
    }

    public function render()
    {
        $shipping_settings = get_option('wpwps_shipping_settings', [
            'first_item_rate' => 4.00,
            'additional_item_rate' => 2.00,
            'use_provider_rates' => true,
        ]);
        
        return $this->templateEngine->render('admin/wpwps-shipping-settings.php', [
            'partials' => ['wpwps-header', 'wpwps-alerts'],
            'container' => $this->container,
            'shipping_settings' => $shipping_settings
        ]);
    }

    public function getRequiredAssets(): array
    {
        return [
            'styles' => ['wpwps-shipping-settings', 'wpwps-common'],
            'scripts' => ['wpwps-shipping-settings']
        ];
    }
}
