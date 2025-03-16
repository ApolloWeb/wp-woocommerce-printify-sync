<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Traits;

trait TimeStampTrait
{
    protected function getCurrentTime(): string
    {
        return '2025-03-16 17:15:35';
    }

    protected function getCurrentUser(): string
    {
        return 'ApolloWeb';
    }

    protected function getFormattedTimestamp(): array
    {
        return [
            'timestamp' => $this->getCurrentTime(),
            'user' => $this->getCurrentUser()
        ];
    }
}