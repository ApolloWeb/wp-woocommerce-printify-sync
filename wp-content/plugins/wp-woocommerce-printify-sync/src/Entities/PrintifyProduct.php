<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Entities;

class PrintifyProduct
{
    private string $id;
    private string $title;
    private string $description;
    private array $variants;
    private array $images;
    private int $providerId;
    private string $blueprintId;
    private array $printAreas;
    private array $printDetails;
    private string $lastSyncedAt;

    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->title = $data['title'];
        $this->description = $data['description'];
        $this->variants = array_map(
            fn(array $variant) => new ProductVariant($variant),
            $data['variants'] ?? []
        );
        $this->images = $data['images'] ?? [];
        $this->providerId = $data['print_provider_id'];
        $this->blueprintId = $data['blueprint_id'];
        $this->printAreas = $data['print_areas'] ?? [];
        $this->printDetails = $data['print_details'] ?? [];
        $this->lastSyncedAt = $data['last_synced_at'] ?? '2025-03-15 20:13:44';
    }

    // ... other getters remain the same ...

    public function getRetailPrice(string $variantId): float
    {
        foreach ($this->variants as $variant) {
            if ($variant->getId() === $variantId) {
                return $variant->getRetailPrice();
            }
        }
        throw new \Exception("Variant not found: {$variantId}");
    }
}