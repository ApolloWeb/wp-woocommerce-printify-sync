<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Asset\Collection;

use ApolloWeb\WPWooCommercePrintifySync\Asset\AssetInterface;

abstract class AbstractAssetCollection implements AssetCollectionInterface
{
    protected array $assets = [];

    public function add(AssetInterface $asset): void
    {
        $this->assets[$asset->getHandle()] = $asset;
    }

    public function get(string $handle): ?AssetInterface
    {
        return $this->assets[$handle] ?? null;
    }

    public function all(): array
    {
        return $this->assets;
    }

    abstract public function register(): void;
    abstract public function enqueue(string $handle): void;
    abstract public function enqueueAll(): void;
}