<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Interfaces;

interface ImageHandlerInterface
{
    public function downloadImage(string $url): ?string;
    public function optimizeImage(string $path): ?string;
    public function uploadToWordPress(string $path, int $postId): ?int;
    public function offloadToCloud(string $path): ?string;
    public function cleanupTempFiles(array $paths): void;
}