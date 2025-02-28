/**
 * Logger class for Printify Sync plugin
 *
 * Author: Rob Owen
 *
 * Date: 2025-02-28
 *
 * @package ApolloWeb\WooCommercePrintifySync
 */
<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

class Logger
{
    public function log(string $message, string $level = 'info'): void
    {
        error_log(sprintf("[Printify Import] [%s] %s", strtoupper($level), $message));
    }
}
