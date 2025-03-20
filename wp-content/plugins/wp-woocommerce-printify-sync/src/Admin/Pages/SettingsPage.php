<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Pages;

use ApolloWeb\WPWooCommercePrintifySync\Core\ServiceContainer;

class SettingsPage extends AbstractAdminPage
{
    public function __construct($templateEngine, ServiceContainer $container = null)
    {
        parent::__construct($templateEngine, $container);
        $this->slug = 'wpwps-settings';
        $this->pageTitle = 'Printify Settings';
        $this->menuTitle = 'Settings';
    }

    public function render()
    {
        // Get current currency
        $currency = get_option('wpwps_currency', 'GBP');
        
        return $this->templateEngine->render('admin/wpwps-settings.php', [
            'partials' => ['wpwps-header', 'wpwps-alerts'],
            'shop_id' => get_option('wpwps_printify_shop_id', ''),
            'api_key' => get_option('wpwps_printify_api_key', ''),
            'endpoint' => get_option('wpwps_printify_endpoint', 'https://api.printify.com/v1'),
            'shop_title' => get_option('wpwps_printify_shop_title', ''),
            'currency' => $currency,
            'container' => $this->container
        ]);
    }

    public function getRequiredAssets(): array
    {
        return [
            'styles' => ['wpwps-settings', 'wpwps-common'],
            'scripts' => ['wpwps-settings']
        ];
    }
}
