<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\DataTransferObjects;

class PrintifyProductData
{
    public string $id;
    public string $title;
    public string $description;
    public string $blueprintId;
    public string $printProviderId;
    public string $shopId;
    public array $images;
    public array $variants;
    public array $shippingProfiles;
    public float $retailPrice;
    public float $costPrice;
    public string $sku;
    public array $tags;
    public array $categories;
    public array $metadata;
    public array $printAreas;
    public array $printProviderData;
    public bool $isPublished;
    public string $externalId;
    public string $createdAt;
    public string $updatedAt;

    public static function fromArray(array $data): self
    {
        $dto = new self();
        $dto->id = $data['id'];
        $dto->title = $data['title'];
        $dto->description = $data['description'] ?? '';
        $dto->blueprintId = $data['blueprint_id'];
        $dto->printProviderId = $data['print_provider_id'];
        $dto->shopId = $data['shop_id'];
        $dto->images = $data['images'] ?? [];
        $dto->variants = $data['variants'] ?? [];
        $dto->shippingProfiles = $data['shipping_profiles'] ?? [];
        $dto->retailPrice = (float)($data['retail_price'] ?? 0.00);
        $dto->costPrice = (float)($data['cost_price'] ?? 0.00);
        $dto->sku = $data['sku'] ?? '';
        $dto->tags = $data['tags'] ?? [];
        $dto->categories = $data['categories'] ?? [];
        $dto->metadata = $data['metadata'] ?? [];
        $dto->printAreas = $data['print_areas'] ?? [];
        $dto->printProviderData = $data['print_provider_data'] ?? [];
        $dto->isPublished = $data['is_published'] ?? false;
        $dto->externalId = $data['external_id'] ?? '';
        $dto->createdAt = $data['created_at'] ?? '';
        $dto->updatedAt = $data['updated_at'] ?? '';
        
        return $dto;
    }
}