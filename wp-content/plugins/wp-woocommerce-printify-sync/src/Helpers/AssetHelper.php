<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Helpers;

class AssetHelper
{
    private const VERSION = '1.0.0';
    private string $currentTime = '2025-03-15 18:05:08';
    private string $currentUser = 'ApolloWeb';

    public static function getPageAssets(string $page): array
    {
        return match ($page) {
            'products' => [
                'css' => ['progress-circle', 'products'],
                'js' => ['chart', 'progress-circle', 'products'],
            ],
            'settings' => [
                'css' => ['settings'],
                'js' => ['settings'],
            ],
            default => [
                'css' => ['dashboard'],
                'js' => ['dashboard'],
            ],
        };
    }
}