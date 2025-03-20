<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Pages;

use ApolloWeb\WPWooCommercePrintifySync\Core\ServiceContainer;

class DashboardPage extends AbstractAdminPage
{
    public function __construct($templateEngine, ServiceContainer $container = null)
    {
        parent::__construct($templateEngine, $container);
        $this->slug = 'wpwps-dashboard';
        $this->pageTitle = 'Printify Sync Dashboard';
        $this->menuTitle = 'Dashboard';
    }

    public function render()
    {
        $content = $this->templateEngine->render('admin/wpwps-dashboard.php', [
            'partials' => ['wpwps-header'],
            'data' => [
                'pageTitle' => $this->pageTitle
            ],
            'container' => $this->container
        ]);
        
        return $this->templateEngine->render('admin/wpwps-layout.php', [
            'content' => $content,
            'data' => [
                'pageTitle' => $this->pageTitle
            ]
        ]);
    }

    public function getRequiredAssets(): array
    {
        $scripts = ['wpwps-dashboard', 'wpwps-common', 'wpwps-clear-data'];
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $scripts[] = 'wpwps-debug';
        }
        
        return [
            'styles' => ['wpwps-dashboard', 'wpwps-common'],
            'scripts' => $scripts
        ];
    }
}
