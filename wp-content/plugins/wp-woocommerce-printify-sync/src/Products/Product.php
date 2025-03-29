<?php
declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Products;

class Product
{
    private int $id;
    private string $title;
    private string $description;
    private float $price;
    private array $variants;
    private array $images;
    private string $printifyId;
    private array $attributes;

    public function __construct(array $data)
    {
        $this->id = $data['id'] ?? 0;
        $this->title = $data['title'] ?? '';
        $this->description = $data['description'] ?? '';
        $this->price = (float)($data['price'] ?? 0.00);
        $this->printifyId = $data['printify_id'] ?? '';
        $this->images = $data['images'] ?? [];
        $this->attributes = $this->processAttributes($data['variants'] ?? []);
        $this->variants = array_map(
            fn(array $variant) => new ProductVariant($variant),
            $data['variants'] ?? []
        );
    }

    private function processAttributes(array $variants): array
    {
        $attributes = [];
        foreach ($variants as $variant) {
            foreach ($variant['options'] ?? [] as $name => $value) {
                if (!isset($attributes[$name])) {
                    $attributes[$name] = [];
                }
                if (!in_array($value, $attributes[$name])) {
                    $attributes[$name][] = $value;
                }
            }
        }
        return $attributes;
    }

    public function toWooCommerce(): array
    {
        return [
            'post_title' => $this->title,
            'post_content' => $this->description,
            'post_status' => 'publish',
            'post_type' => 'product',
            'meta_input' => [
                '_price' => $this->price,
                '_regular_price' => $this->price,
                '_printify_id' => $this->printifyId,
                '_manage_stock' => 'yes',
                '_stock_status' => 'instock',
                '_product_attributes' => $this->getWooCommerceAttributes()
            ]
        ];
    }

    private function getWooCommerceAttributes(): array
    {
        $attributes = [];
        foreach ($this->attributes as $name => $values) {
            $attributes[sanitize_title($name)] = [
                'name' => $name,
                'value' => implode('|', $values),
                'position' => 0,
                'is_visible' => 1,
                'is_variation' => 1,
                'is_taxonomy' => 0
            ];
        }
        return $attributes;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getVariants(): array
    {
        return $this->variants;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }
}