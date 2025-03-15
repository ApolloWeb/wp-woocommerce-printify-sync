<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Contracts;

interface ImageHandlerInterface
{
    public function handleImage(string $imageUrl, int $productId): int;
    public function optimizeImage(string $path): void;
    public function cleanup(): void;
}