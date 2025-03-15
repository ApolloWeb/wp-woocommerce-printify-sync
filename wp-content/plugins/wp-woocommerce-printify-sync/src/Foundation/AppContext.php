<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Foundation;

class AppContext
{
    private static ?AppContext $instance = null;
    private string $currentTime = '2025-03-15 20:15:31';
    private string $currentUser = 'ApolloWeb';
    private string $environment;

    private function __construct()
    {
        $this->environment = defined('WP_ENV') ? WP_ENV : 'production';
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getCurrentTime(): string
    {
        return $this->currentTime;
    }

    public function getCurrentUser(): string
    {
        return $this->currentUser;
    }

    public function isProduction(): bool
    {
        return $this->environment === 'production';
    }

    public function isDevelopment(): bool
    {
        return $this->environment === 'development';
    }

    public function getEnvironment(): string
    {
        return $this->environment;
    }
}