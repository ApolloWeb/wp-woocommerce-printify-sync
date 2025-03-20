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
        return $this->templateEngine->render('admin/wpwps-settings.php', [
            'partials' => ['wpwps-header', 'wpwps-alerts'],
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
