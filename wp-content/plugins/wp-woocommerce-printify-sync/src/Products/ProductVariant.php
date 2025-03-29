<?php
declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Products;

class ProductVariant
{
    private string $sku;
    private float $price;
    private array $options;
    private bool $isEnabled;
    private int $quantity;

    public function __construct(array $data)
    {
        $this->sku = $data['sku'] ?? '';
        $this->price = (float)($data['price'] ?? 0.00);
        $this->options = $data['options'] ?? [];
        $this->isEnabled = $data['is_enabled'] ?? true;
        $this->quantity = (int)($data['quantity'] ?? 0);
    }

    public function toWooCommerceVariation(): array
    {
        return [
            'post_title' => 'Product variation',
            'post_name' => sanitize_title($this->sku),
            'post_status' => $this->isEnabled ? 'publish' : 'private',
            'post_type' => 'product_variation',
            'meta_input' => [
                '_sku' => $this->sku,
                '_price' => $this->price,
                '_regular_price' => $this->price,
                '_stock' => $this->quantity,
                '_stock_status' => $this->quantity > 0 ? 'instock' : 'outofstock',
                '_manage_stock' => 'yes'
            ]
        ];
    }

    public function getAttributes(): array
    {
        return $this->options;
    }

    public function getSku(): string
    {
        return $this->sku;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }
}