<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Logging;

abstract class AbstractLogger implements LoggerInterface
{
    protected string $currentTime = '2025-03-15 20:02:53';
    protected string $currentUser = 'ApolloWeb';

    protected function formatMessage(string $level, string $message, array $context = []): string
    {
        return sprintf(
            '[%s] [%s] [%s] %s %s',
            $this->currentTime,
            $level,
            $this->currentUser,
            $message,
            !empty($context) ? ' - ' . json_encode($context) : ''
        );
    }

    protected function interpolate(string $message, array $context = []): string
    {
        $replace = [];
        foreach ($context as $key => $val) {
            $replace['{' . $key . '}'] = is_scalar($val) ? (string) $val : json_encode($val);
        }

        return strtr($message, $replace);
    }
}