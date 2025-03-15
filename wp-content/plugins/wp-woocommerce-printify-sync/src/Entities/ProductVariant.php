<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Entities;

class ProductVariant
{
    private string $id;
    private string $sku;
    private float $retailPrice;
    private array $options;
    private bool $isEnabled;
    private bool $isDefault;
    private int $grams;
    private bool $isShippingEnabled;

    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->sku = $data['sku'];
        $this->retailPrice = (float) $data['retail_price'];
        $this->options = $data['options'] ?? [];
        $this->isEnabled = $data['is_enabled'] ?? true;
        $this->isDefault = $data['is_default'] ?? false;
        $this->grams = (int) ($data['grams'] ?? 0);
        $this->isShippingEnabled = $data['is_shipping_enabled'] ?? true;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getSku(): string
    {
        return $this->sku;
    }

    public function getRetailPrice(): float
    {
        return $this->retailPrice;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    public function getGrams(): int
    {
        return $this->grams;
    }

    public function isShippingEnabled(): bool
    {
        return $this->isShippingEnabled;
    }
}