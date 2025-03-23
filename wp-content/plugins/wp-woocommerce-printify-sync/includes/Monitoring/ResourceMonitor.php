<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Monitoring;

class ResourceMonitor {
    private $logger;
    private $thresholds;

    public function __construct(Logger $logger) {
        $this->logger = $logger;
        $this->thresholds = [
            'memory' => 85,  // 85% memory usage threshold
            'cpu' => 80,     // 80% CPU usage threshold
            'gpu' => 90      // 90% GPU utilization threshold
        ];
    }

    public function getResourceMetrics(): array {
        return [
            'memory_usage' => $this->getMemoryUsage(),
            'cpu_load' => $this->getCPULoad(),
            'gpu_utilization' => $this->getGPUUtilization(),
            'disk_usage' => $this->getDiskUsage(),
            'network_stats' => $this->getNetworkStats()
        ];
    }

    private function getMemoryUsage(): array {
        $memory_limit = ini_get('memory_limit');
        $memory_usage = memory_get_usage(true);
        $peak_usage = memory_get_peak_usage(true);

        return [
            'current' => $this->formatBytes($memory_usage),
            'peak' => $this->formatBytes($peak_usage),
            'limit' => $memory_limit,
            'percentage' => ($memory_usage / $this->getMemoryLimitInBytes($memory_limit)) * 100
        ];
    }

    private function formatBytes($bytes): string {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        return round($bytes / (1024 ** $pow), 2) . ' ' . $units[$pow];
    }
}
