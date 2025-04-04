<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Contracts;

interface NavigatorInterface {
    /**
     * Get navigation structure
     * 
     * @return array Navigation items organized by section
     */
    public function getNavigation(): array;
}
