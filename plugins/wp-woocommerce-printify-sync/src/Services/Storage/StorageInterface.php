<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services\Storage;

interface StorageInterface
{
    public function store(string $sourcePath, string $destinationPath): ?string;
    public function getPublicUrl(string $path): string;
    public function getProviderName(): string;
    public function delete(string $path): bool;
}