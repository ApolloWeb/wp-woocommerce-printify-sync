<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Utilities;

class Environment
{
    public static function isDevelopment()
    {
        return defined('WP_ENV') && WP_ENV === 'development';
    }
}