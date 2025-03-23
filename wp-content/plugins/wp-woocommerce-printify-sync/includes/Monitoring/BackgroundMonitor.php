<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Monitoring;

class BackgroundMonitor {
    private $metrics_collector;
    private $logger;
    private $alert_thresholds;

    public function __construct(MetricsCollector $metrics_collector, Logger $logger) {
        $this->metrics_collector = $metrics_collector;
        $this->logger = $logger;
        $this->alert_thresholds = [
            'memory_usage' => 85,
            'cpu_usage' => 80,
            'error_rate' => 5
        ];
    }

    public function monitor(): void {
        $metrics = $this->metrics_collector->collectSystemMetrics();
        $this->analyzeMetrics($metrics);
        $this->storeMetrics($metrics);
    }

    private function analyzeMetrics(array $metrics): void {
        foreach ($this->alert_thresholds as $metric => $threshold) {
            if ($metrics['system'][$metric] > $threshold) {
                $this->triggerAlert($metric, $metrics['system'][$metric]);
            }
        }
    }
}
