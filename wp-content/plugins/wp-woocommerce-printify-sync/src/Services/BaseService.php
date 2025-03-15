<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use ApolloWeb\WPWooCommercePrintifySync\Foundation\{
    TimeProvider,
    UserProvider
};
use ApolloWeb\WPWooCommercePrintifySync\Logging\LoggerAwareTrait;

abstract class BaseService
{
    use LoggerAwareTrait;

    public function __construct(
        protected TimeProvider $timeProvider,
        protected UserProvider $userProvider
    ) {}

    protected function getCurrentTime(): string
    {
        return $this->timeProvider->getCurrentTime();
    }

    protected function getCurrentUser(): string
    {
        return $this->userProvider->getCurrentUser();
    }
}