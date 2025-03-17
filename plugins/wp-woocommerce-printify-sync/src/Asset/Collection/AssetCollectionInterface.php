<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Asset\Collection;

use ApolloWeb\WPWooCommercePrintifySync\Asset\AssetInterface;

interface AssetCollectionInterface
{
    public function add(AssetInterface $asset): void;
    public function get(string $handle): ?AssetInterface;
    public function all(): array;
    public function register(): void;
    public function enqueue(string $handle): void;
    public function enqueueAll(): void;
}