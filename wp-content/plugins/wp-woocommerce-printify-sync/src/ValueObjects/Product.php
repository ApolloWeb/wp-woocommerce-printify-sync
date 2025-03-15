<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\ValueObjects;

class Product
{
    private string $id;
    private string $title;
    private string $description;
    private array $variants;
    private array $images;
    private array $metadata;

    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->title = $data['title'];
        $this->description = $data['description'];
        $this->variants = array_map(
            fn(array $variant) => new Variant($variant),
            $data['variants'] ?? []
        );
        $this->images = array_map(
            fn(array $image) => new Image($image),
            $data['images'] ?? []
        );
        $this->metadata = $data['metadata'] ?? [];
    }

    public function getId(): string
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

    public function getVariants(): array
    {
        return $this->variants;
    }

    public function getImages(): array
    {
        return $this->images;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'variants' => array_map(fn($variant) => $variant->toArray(), $this->variants),
            'images' => array_map(fn($image) => $image->toArray(), $this->images),
            'metadata' => $this->metadata
        ];
    }
}