<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Monitoring;

class PerformanceMonitor {
    private $cache;
    private $logger;
    private $metrics = [];

    public function __construct(CacheManager $cache, Logger $logger) {
        $this->cache = $cache;
        $this->logger = $logger;
    }

    public function collectMetrics(): array {
        return [
            'cache_metrics' => $this->getCacheMetrics(),
            'load_metrics' => $this->getLoadMetrics(),
            'response_metrics' => $this->getResponseMetrics(),
            'system_health' => $this->getSystemHealth()
        ];
    }

    private function getCacheMetrics(): array {
        $stats = $this->cache->getStats();
        return [
            'hit_rate' => $this->calculateHitRate($stats),
            'memory_usage' => $this->cache->getMemoryUsage(),
            'eviction_count' => $stats['evictions'] ?? 0
        ];
    }

    private function calculateHitRate(array $stats): float {
        $hits = $stats['hits'] ?? 0;
        $misses = $stats['misses'] ?? 0;
        $total = $hits + $misses;
        
        return $total > 0 ? ($hits / $total) * 100 : 0;
    }

    private function getSystemHealth(): string {
        $metrics = $this->collectSystemMetrics();
        
        if ($metrics['memory_usage'] > 85 || $metrics['cpu_load'] > 90) {
            return 'critical';
        } elseif ($metrics['memory_usage'] > 75 || $metrics['cpu_load'] > 80) {
            return 'warning';
        }
        
        return 'healthy';
    }
}
