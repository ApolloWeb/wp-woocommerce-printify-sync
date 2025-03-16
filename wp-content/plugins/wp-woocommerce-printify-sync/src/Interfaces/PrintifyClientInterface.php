<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Interfaces;

interface PrintifyClientInterface
{
    public function getProduct(string $printifyId): array;
    public function getProductVariants(string $printifyId): array;
    public function getShippingProfiles(string $shopId): array;
    public function getBlueprint(string $blueprintId): array;
}