<?php

namespace ApolloWeb\WPWooCommercePrintifySync\API\Interfaces;

interface ShopManagementInterface
{
    /**
     * Get shops from Printify
     *
     * @return array
     */
    public function getShops(): array;
}
