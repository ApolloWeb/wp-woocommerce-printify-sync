<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Ajax\Handlers;

use ApolloWeb\WPWooCommercePrintifySync\Core\ServiceContainer;

abstract class BaseHandler
{
    protected $container;
    
    public function __construct(ServiceContainer $container)
    {
        $this->container = $container;
    }

    protected function verifyShopId(): string
    {
        $shopId = get_option('wpwps_printify_shop_id', '');
        if (empty($shopId)) {
            throw new \Exception('Shop ID not configured');
        }
        return $shopId;
    }

    protected function verifyGetRequest()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            throw new \Exception('This endpoint only accepts GET requests');
        }
    }

    protected function getPaginationParams(): array
    {
        return [
            'page' => isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1,
            'per_page' => isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10
        ];
    }
}
