<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Pages;

class SettingsPage extends AbstractAdminPage
{
    public function __construct($templateEngine)
    {
        parent::__construct($templateEngine);
        $this->slug = 'wpwps-settings';
        $this->pageTitle = 'Printify Settings';
        $this->menuTitle = 'Settings';
    }

    public function render()
    {
        return $this->templateEngine->render('admin/wpwps-settings.php', [
            'partials' => ['wpwps-header', 'wpwps-alerts']
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
