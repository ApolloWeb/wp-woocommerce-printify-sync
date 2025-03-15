<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class UserContext
{
    private string $currentUserLogin;

    public function __construct(string $currentUserLogin)
    {
        $this->currentUserLogin = $currentUserLogin;
    }

    public function getCurrentUserLogin(): string
    {
        return $this->currentUserLogin;
    }

    public function hasPermission(string $capability): bool
    {
        return current_user_can($capability);
    }

    public function isAuthenticated(): bool
    {
        return $this->currentUserLogin !== 'unknown';
    }
}