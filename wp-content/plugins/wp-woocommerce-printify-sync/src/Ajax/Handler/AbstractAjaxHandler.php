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
    
    /**
     * Get pagination parameters
     * 
     * @param int $defaultPerPage Default items per page
     * @return array
     */
    protected function getPagination(int $defaultPerPage = 10): array
    {
        return [
            'page' => isset($_REQUEST['page']) ? max(1, (int)$_REQUEST['page']) : 1,
            'per_page' => isset($_REQUEST['per_page']) ? min((int)$_REQUEST['per_page'], $defaultPerPage) : $defaultPerPage
        ];
    }
    
    /**
     * Check if cache should be refreshed
     * 
     * @return bool
     */
    protected function shouldRefreshCache(): bool
    {
        return isset($_REQUEST['refresh_cache']) && $_REQUEST['refresh_cache'] === 'true';
    }
}
