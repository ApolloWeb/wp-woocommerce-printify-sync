<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Settings;

abstract class AbstractSettingsSection
{
    protected string $currentTime = '2025-03-15 18:56:10';
    protected string $currentUser = 'ApolloWeb';
    protected string $sectionId;
    protected string $sectionTitle;

    abstract public function registerSettings(): void;
    abstract public function renderSection(): void;
    abstract public function testConnection(): array;
    abstract public function validateSettings(array $settings): array;
}