<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Pages;

class SettingsPage extends AbstractPage
{
    public function __construct()
    {
        $this->template = 'wpwps-settings';
        $this->pageSlug = 'settings';
    }
}
