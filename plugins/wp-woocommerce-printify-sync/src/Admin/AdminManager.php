<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

class AdminManager
{
    private MenuInterface $menu;
    private SettingsInterface $settings;

    public function __construct(MenuInterface $menu, SettingsInterface $settings)
    {
        $this->menu = $menu;
        $this->settings = $settings;
    }

    public function initialize(): void
    {
        add_action('admin_menu', [$this->menu, 'register']);
        add_action('admin_init', [$this->settings, 'register']);
    }
}