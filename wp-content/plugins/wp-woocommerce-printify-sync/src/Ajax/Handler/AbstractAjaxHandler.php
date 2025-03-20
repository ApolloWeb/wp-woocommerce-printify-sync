<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Ajax\Handler;

use ApolloWeb\WPWooCommercePrintifySync\Core\ServiceContainer;

abstract class AbstractAjaxHandler
{
    /**
     * @var ServiceContainer
     */
    protected $container;
    
    /**
     * Constructor
     * 
     * @param ServiceContainer $container Service container
     */
    public function __construct(ServiceContainer $container)
    {
        $this->container = $container;
    }
    
    /**
     * Verify Shop ID exists and return it
     * 
     * @return string
     * @throws \Exception
     */
    protected function getShopId(): string
    {
        $shopId = get_option('wpwps_printify_shop_id', '');
        if (empty($shopId)) {
            throw new \Exception('Shop ID not configured');
        }
        return $shopId;
    }
}
