<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Models;

use ApolloWeb\WPWooCommercePrintifySync\Contracts\ProductInterface;

class Product implements ProductInterface
{
    private int $id;
    private string $title;
    private string $description;
    private float $price;
    private array $images;
    private array $variants;
    private ?string $createdAt;
    private ?string $updatedAt;

    public function __construct(array $data)
    {
        $this->id = (int) $data['id'];
        $this->title = $data['title'];
        $this->description = $data['description'] ?? '';
        $this->price = (float) $data['price'];
        $this->images = $data['images'] ?? [];
        $this->variants = $data['variants'] ?? [];
        $this->createdAt = $data['created_at'] ?? null;
        $this->updatedAt = $data['updated_at'] ?? null;
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

    public function getImages(): array
    {
        return $this->images;
    }

    public function getVariants(): array
    {
        return $this->variants;
    }

    public function toWooCommerceProduct(): array
    {
        return [
            'post_title' => $this->getTitle(),
            'post_content' => $this->getDescription(),
            'post_status' => 'publish',
            'post_type' => 'product',
            'meta_input' => [
                '_price' => $this->getPrice(),
                '_regular_price' => $this->getPrice(),
                '_printify_product_id' => $this->getId(),
                '_printify_variants' => $this->getVariants(),
                '_printify_created_at' => $this->createdAt,
                '_printify_updated_at' => $this->updatedAt
            ]
        ];
    }
}