<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class DashboardService
{
    private string $currentTime;
    private string $currentUser;

    public function __construct(string $currentTime, string $currentUser)
    {
        $this->currentTime = $currentTime; // 2025-03-15 18:17:48
        $this->currentUser = $currentUser; // ApolloWeb
    }

    public function getSalesData(string $period = 'day'): array
    {
        // Get real data if available, otherwise use dummy data
        return $this->generateDummySalesData($period);
    }

    public function getWidgetData(): array
    {
        return [
            'quick_stats' => $this->getQuickStats(),
            'recent_orders' => $this->getRecentOrders(),
            'top_products' => $this->getTopProducts(),
            'sync_status' => $this->getSyncStatus(),
            'api_health' => $this->getApiHealth(),
            'recent_logs' => $this->getRecentLogs()
        ];
    }

    private function generateDummySalesData(string $period): array
    {
        $end = strtotime($this->currentTime);
        $data = [];
        $labels = [];

        switch ($period) {
            case 'day':
                $start = strtotime('-24 hours', $end);
                $interval = 'PT1H';
                $format = 'H:i';
                break;
            case 'week':
                $start = strtotime('-7 days', $end);
                $interval = 'P1D';
                $format = 'D';
                break;
            case 'month':
                $start = strtotime('-30 days', $end);
                $interval = 'P1D';
                $format = 'd M';
                break;
            case 'year':
                $start = strtotime('-1 year', $end);
                $interval = 'P1M';
                $format = 'M Y';
                break;
            default:
                throw new \InvalidArgumentException('Invalid period');
        }

        $period = new \DatePeriod(
            new \DateTime('@' . $start),
            new \DateInterval($interval),
            new \DateTime('@' . $end)
        );

        foreach ($period as $date) {
            $labels[] = $date->format($format);
            
            // Generate realistic looking dummy data
            $baseAmount = 1000;
            $variance = $baseAmount * 0.3;
            $amount = $baseAmount + (rand(-$variance * 100, $variance * 100) / 100);
            
            // Add weekly pattern
            if ($date->format('N') >= 6) { // Weekend
                $amount *= 1.5;
            }
            
            // Add time of day pattern for hourly data
            if ($period === 'day') {
                $hour = (int)$date->format('H');
                if ($hour >= 9 && $hour <= 17) { // Business hours
                    $amount *= 1.3;
                }
            }

            $data[] = round($amount, 2);
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Sales',
                    'data' => $data,
                    'borderColor' => '#2271b1',
                    'backgroundColor' => 'rgba(34, 113, 177, 0.1)',
                    'fill' => true
                ]
            ]
        ];
    }

    private function getQuickStats(): array
    {
        return [
            'total_products' => [
                'value' => rand(100, 1000),
                'change' => rand(-10, 20),
                'label' => 'Total Products',
                'icon' => 'fa-boxes'
            ],
            'synced_products' => [
                'value' => rand(50, 500),
                'change' => rand(-5, 15),
                'label' => 'Synced Products',
                'icon' => 'fa-sync'
            ],
            'total_orders' => [
                'value' => rand(1000, 5000),
                'change' => rand(-20, 40),
                'label' => 'Total Orders',
                'icon' => 'fa-shopping-cart'
            ],
            'revenue' => [
                'value' => rand(10000, 50000),
                'change' => rand(-15, 30),
                'label' => 'Revenue',
                'icon' => 'fa-dollar-sign',
                'format' => 'currency'
            ]
        ];
    }

    private function getRecentOrders(): array
    {
        $statuses = ['processing', 'completed', 'on-hold'];
        $orders = [];

        for ($i = 0; $i < 5; $i++) {
            $orderTime = strtotime("-{$i} hours", strtotime($this->currentTime));
            $orders[] = [
                'id' => rand(1000, 9999),
                'status' => $statuses[array_rand($statuses)],
                'amount' => rand(50, 500),
                'items' => rand(1, 5),
                'customer' => "Customer " . rand(1, 100),
                'date' => date('Y-m-d H:i:s', $orderTime)
            ];
        }

        return $orders;
    }

    private function getTopProducts(): array
    {
        $products = [];
        for ($i = 0; $i < 5; $i++) {
            $products[] = [
                'id' => rand(1, 1000),
                'name' => "Product " . rand(1, 100),
                'sales' => rand(50, 500),
                'revenue' => rand(1000, 10000),
                'stock' => rand(0, 100)
            ];
        }
        return $products;
    }

    private function getSyncStatus(): array
    {
        return [
            'last_sync' => date('Y-m-d H:i:s', strtotime('-2 hours', strtotime($this->currentTime))),
            'sync_status' => 'success',
            'products_synced' => rand(10, 50),
            'products_failed' => rand(0, 5),
            'next_sync' => date('Y-m-d H:i:s', strtotime('+1 hour', strtotime($this->currentTime)))
        ];
    }

    private function getApiHealth(): array
    {
        return [
            'status' => 'healthy',
            'rate_limit_remaining' => rand(70, 100),
            'average_response_time' => rand(100, 500),
            'errors_24h' => rand(0, 10),
            'uptime' => '99.9%'
        ];
    }

    private function getRecentLogs(): array
    {
        $types = ['info', 'warning', 'error', 'success'];
        $logs = [];

        for ($i = 0; $i < 5; $i++) {
            $logTime = strtotime("-{$i} minutes", strtotime($this->currentTime));
            $logs[] = [
                'type' => $types[array_rand($types)],
                'message' => "Log message " . rand(1, 100),
                'date' => date('Y-m-d H:i:s', $logTime)
            ];
        }

        return $logs;
    }
}