<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Interfaces;

interface SettingsInterface {
    public function init(): void;
    public function renderDashboard(): void;
    public function renderSettings(): void;
}
