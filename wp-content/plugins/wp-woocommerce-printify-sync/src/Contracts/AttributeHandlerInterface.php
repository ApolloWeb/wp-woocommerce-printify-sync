<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Contracts;

interface AttributeHandlerInterface
{
    public function createAttribute(string $name, array $options = []): int;
    public function addAttributeToProduct(int $productId, string $attributeName, array $values, bool $isVariation = false): void;
    public function createVariation(int $productId, array $attributes, array $data): int;
}