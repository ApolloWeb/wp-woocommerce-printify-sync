<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Pages;

class DashboardPage extends AbstractAdminPage
{
    public function __construct($templateEngine, $container)
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
                'pageTitle' => $this->pageTitle,
                'container' => $this->container
            ]
        ]);
        
        return $this->templateEngine->render('admin/wpwps-layout.php', [
            'content' => $content,
            'data' => [
                'pageTitle' => $this->pageTitle
            ]
        ]);
    }

    // ...existing code...
}
