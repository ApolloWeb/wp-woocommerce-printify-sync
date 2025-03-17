<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use ApolloWeb\WPWooCommercePrintifySync\Context\SyncContext;
use ApolloWeb\WPWooCommercePrintifySync\Interfaces\LoggerInterface;
use ApolloWeb\WPWooCommercePrintifySync\Services\Storage\StorageInterface;

class ImageOptimizationService
{
    private LoggerInterface $logger;
    private SyncContext $context;
    private StorageInterface $storage;

    public function __construct(
        LoggerInterface $logger,
        SyncContext $context,
        StorageInterface $storage
    ) {
        $this->logger = $logger;
        $this->context = $context;
        $this->storage = $storage;
    }

    public function optimizeAndStore(string $imagePath, string $destinationPath): array
    {
        try {
            // Generate WebP version
            $webpPath = $this->generateWebP($imagePath);
            
            // Store original
            $storedPath = $this->storage->store($imagePath, $destinationPath);
            
            // Store WebP if generated
            $webpStoredPath = null;
            if ($webpPath) {
                $webpStoredPath = $this->storage->store(
                    $webpPath,
                    $this->getWebPPath($destinationPath)
                );
            }

            return [
                'original_path' => $storedPath,
                'webp_path' => $webpStoredPath,
                'provider' => $this->storage->getProviderName()
            ];

        } finally {
            // Cleanup
            if (isset($webpPath) && file_exists($webpPath)) {
                @unlink($webpPath);
            }
        }
    }

    private function generateWebP(string $sourcePath): ?string
    {
        if (!function_exists('imagewebp')) {
            $this->logger->warning('WebP conversion not available');
            return null;
        }

        try {
            $image = $this->createImageResource($sourcePath);
            if (!$image) {
                return null;
            }

            $webpPath = $this->getTempPath($sourcePath, 'webp');
            imagewebp($image, $webpPath, 80);
            imagedestroy($image);

            return $webpPath;
        } catch (\Exception $e) {
            $this->logger->error('WebP conversion failed', [
                'source' => $sourcePath,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    private function createImageResource(string $path): \GdImage|false
    {
        $type = exif_imagetype($path);
        return match ($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($path),
            IMAGETYPE_PNG => imagecreatefrompng($path),
            default => false
        };
    }

    private function getWebPPath(string $originalPath): string
    {
        return preg_replace('/\.[^.]+$/', '.webp', $originalPath);
    }

    private function getTempPath(string $originalPath, string $extension): string
    {
        return sys_get_temp_dir() . '/' . uniqid('wpwps_', true) . '.' . $extension;
    }
}