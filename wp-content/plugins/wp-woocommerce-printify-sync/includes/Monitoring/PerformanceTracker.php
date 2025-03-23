<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Monitoring;

class PerformanceTracker {
    private $metrics_collector;
    private $logger;
    private $tracking_interval = 60; // 1 minute

    public function __construct(MetricsCollector $metrics_collector, Logger $logger) {
        $this->metrics_collector = $metrics_collector;
        $this->logger = $logger;
    }

    public function trackPerformance(): array {
        $metrics = $this->metrics_collector->collectSystemMetrics();
        $this->storeMetrics($metrics);
        $this->analyzePerformance($metrics);
        
        return [
            'current' => $metrics,
            'trends' => $this->calculateTrends(),
            'health_status' => $this->assessSystemHealth($metrics)
        ];
    }

    private function analyzePerformance(array $metrics): void {
        $this->detectAnomalies($metrics);
        $this->checkThresholds($metrics);
        $this->updateHealthStatus($metrics);
    }

    private function detectAnomalies(array $metrics): void {
        $baseline = $this->getBaseline();
        $anomalies = [];

        foreach ($metrics as $key => $value) {
            if ($this->isAnomaly($value, $baseline[$key] ?? null)) {
                $anomalies[$key] = [
                    'value' => $value,
                    'baseline' => $baseline[$key],
                    'deviation' => $this->calculateDeviation($value, $baseline[$key])
                ];
            }
        }

        if (!empty($anomalies)) {
            $this->logger->log('Performance anomalies detected: ' . json_encode($anomalies), 'warning');
        }
    }
}
