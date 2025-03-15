<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Logging;

class LoggerFactory
{
    public static function create(string $type = 'database'): LoggerInterface
    {
        return match ($type) {
            'database' => new DatabaseLogger(),
            'file' => new FileLogger(),
            default => throw new \InvalidArgumentException('Invalid logger type')
        };
    }
}