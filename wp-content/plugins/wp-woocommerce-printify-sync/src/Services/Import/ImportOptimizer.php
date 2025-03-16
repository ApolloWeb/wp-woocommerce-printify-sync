<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services\Import;

use ApolloWeb\WPWooCommercePrintifySync\Traits\TimeStampTrait;

class ImportOptimizer
{
    use TimeStampTrait;

    private const CACHE_GROUP = 'wpwps_import';
    private const MEMORY_THRESHOLD = 0.8; // 80%
    private const BATCH_SIZE_MIN = 5;
    private const BATCH_SIZE_MAX = 50;

    private ImportMonitor $monitor;
    private LoggerInterface $logger;
    private CacheInterface $cache;

    public function __construct(
        ImportMonitor $monitor,
        LoggerInterface $logger,
        CacheInterface $cache
    ) {
        $this->monitor = $monitor;
        $this->logger = $logger;
        $this->cache = $cache;
    }

    public function optimizeBatchSize(string $batchId): int
    {
        $metrics = $this->monitor->getImportMetrics($batchId);
        $systemLoad = $this->getSystemLoad();
        
        // Start with current batch size
        $currentSize = $metrics['batch_size'] ?? 10;
        
        // Adjust based on performance metrics
        if ($metrics['success_rate'] > 95 && $systemLoad < 0.7) {
            $currentSize = min($currentSize * 1.2, self::BATCH_SIZE_MAX);
        } elseif ($metrics['error_rate'] > 10 || $systemLoad > 0.8) {
            $currentSize = max($currentSize * 0.8, self::BATCH_SIZE_MIN);
        }

        return (int) $currentSize;
    }

    public function shouldThrottle(): bool
    {
        return (
            $this->getMemoryUsage() > self::MEMORY_THRESHOLD ||
            $this->getSystemLoad() > 0.9
        );
    }

    public function getCacheKey(string $productId): string
    {
        return 'product_' . md5($productId . $this->getCurrentTime());
    }

    public function cacheProductData(string $productId, array $data): void
    {
        $key = $this->getCacheKey($productId);
        $this->cache->set($key, $data, self::CACHE_GROUP, 3600);
    }

    public function getCachedProductData(string $productId): ?array
    {
        $key = $this->getCacheKey($productId);
        return $this->cache->get($key, self::CACHE_GROUP);
    }

    public function optimizeImageImport(array $images): array
    {
        return array_map(function($image) {
            // Skip if already optimized
            if (isset($image['optimized'])) {
                return $image;
            }

            return [
                'url' => $image['url'],
                'optimized' => true,
                'lazy_load' => true,
                'srcset' => $this->generateSrcSet($image['url']),
                'sizes' => $this->generateSizes($image['dimensions'] ?? null)
            ];
        }, $images);
    }

    private function getSystemLoad(): float
    {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            return $load[0] / 100;
        }
        return 0.5; // Default moderate load if unavailable
    }

    private function getMemoryUsage(): float
    {
        $limit = ini_get('memory_limit');
        $used = memory_get_usage(true);

        // Convert memory limit to bytes
        if (preg_match('/^(\d+)(.)$/', $limit, $matches)) {
            $limit = $matches[1];
            switch ($matches[2]) {
                case 'G': $limit *= 1024;
                case 'M': $limit *= 1024;
                case 'K': $limit *= 1024;
            }
        }

        return $used / $limit;
    }

    private function generateSrcSet(string $url): string
    {
        $sizes = [320, 640, 1024, 1920];
        $srcset = [];

        foreach ($sizes as $size) {
            $srcset[] = $this->resizeImageUrl($url, $size) . " {$size}w";
        }

        return implode(', ', $srcset);
    }

    private function generateSizes(?array $dimensions): string
    {
        if (!$dimensions) {
            return '(max-width: 1920px) 100vw, 1920px';
        }

        $ratio = $dimensions['width'] / $dimensions['height'];
        return "(max-width: 768px) 100vw, {$dimensions['width']}px";
    }

    private function resizeImageUrl(string $url, int $width): string
    {
        // Implement image resizing URL generation
        // This could be a CDN URL or a local image resizing endpoint
        return $url; // Placeholder
    }
}