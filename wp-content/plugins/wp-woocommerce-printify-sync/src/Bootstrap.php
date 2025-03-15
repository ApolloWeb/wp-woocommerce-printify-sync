<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync;

use ApolloWeb\WPWooCommercePrintifySync\Controllers\AdminMenuController;

class Bootstrap
{
    public function init(array $config): void
    {
        new AdminMenuController(
            $config['currentTime'],  // 2025-03-15 17:54:44
            $config['currentUser']   // ApolloWeb
        );
    }
}