<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Entities;

class ShippingProfile
{
    private int $id;
    private string $name;
    private int $providerId;
    private string $calculationType;
    private array $regions;
    private array $rates;
    private bool $isDefault;

    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->name = $data['name'];
        $this->providerId = $data['print_provider_id'];
        $this->calculationType = $data['calculation_type'];
        $this->regions = $data['regions'] ?? [];
        $this->rates = $data['rates'] ?? [];
        $this->isDefault = $data['is_default'] ?? false;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getProviderId(): int
    {
        return $this->providerId;
    }

    public function getCalculationType(): string
    {
        return $this->calculationType;
    }

    public function getRegions(): array
    {
        return $this->regions;
    }

    public function getRates(): array
    {
        return $this->rates;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    public function calculateShippingCost(string $countryCode, array $items): float
    {
        $region = $this->findRegion($countryCode);
        if (!$region) {
            return 0.0;
        }

        return match ($this->calculationType) {
            'flat_rate' => $this->calculateFlatRate($region),
            'weight_based' => $this->calculateWeightBased($region, $items),
            'item_count' => $this->calculateItemCount($region, $items),
            default => 0.0
        };
    }

    private function findRegion(string $countryCode): ?array
    {
        foreach ($this->regions as $region) {
            if (in_array($countryCode, $region['countries'])) {
                return $region;
            }
        }
        return null;
    }

    private function calculateFlatRate(array $region): float
    {
        return (float) ($region['flat_rate'] ?? 0.0);
    }

    private function calculateWeightBased(array $region, array $items): float
    {
        $totalWeight = array_sum(array_map(fn($item) => $item['weight'] * $item['quantity'], $items));
        $rates = $region['weight_rates'] ?? [];

        foreach ($rates as $rate) {
            if ($totalWeight <= $rate['max_weight']) {
                return (float) $rate['cost'];
            }
        }

        return (float) end($rates)['cost'];
    }

    private function calculateItemCount(array $region, array $items): float
    {
        $totalItems = array_sum(array_column($items, 'quantity'));
        $rates = $region['item_rates'] ?? [];

        foreach ($rates as $rate) {
            if ($totalItems <= $rate