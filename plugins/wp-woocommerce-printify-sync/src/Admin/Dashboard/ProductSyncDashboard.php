<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Dashboard;

use ApolloWeb\WPWooCommercePrintifySync\Services\ProductSync;
use ApolloWeb\WPWooCommercePrintifySync\Traits\TimeStampTrait;

class ProductSyncDashboard
{
    use TimeStampTrait;

    private ProductSync $productSync;
    private LoggerInterface $logger;

    public function __construct(ProductSync $productSync, LoggerInterface $logger)
    {
       