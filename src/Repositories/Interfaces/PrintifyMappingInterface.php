<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Repositories\Interfaces;

interface PrintifyMappingInterface {
    public function getWooCommerceId(string $printify_id): ?int;
    public function getPrintifyId(int $wc_product_id): ?string;
    public function getPrintifyVariantId(int $wc_variation_id): ?string;
    public function getVariationIdMap(int $wc_product_id): array;
    public function savePrintifyIds(int $wc_product_id, string $printify_id, array $variant_ids): void;
}
