<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Monitor;

class ServiceMonitor
{
    private const STATUS_OK = 'ok';
    private const STATUS_WARNING = 'warning';
    private const STATUS_ERROR = 'error';

    public function checkServiceStatus(): array
    {
        return [
            'printify_api' => $this->checkPrintifyApi(),
            'woocommerce' => $this->checkWooCommerce(),
            'background_processing' => $this->checkBackgroundProcessing(),
            'database' => $this->checkDatabase(),
            'filesystem' => $this->checkFileSystem()
        ];
    }

    private function checkPrintifyApi(): array
    {
        try {
            // Test API connection
            $response = wp_remote_get('https://api.printify.com/v1/shops.json', [
                'headers' => [
                    'Authorization' => 'Bearer ' . get_option('wpwps_printify_api_key')
                ]
            ]);

            if (is_wp_error($response)) {
                return [
                    'status' => self::STATUS_ERROR,
                    'message' => $response->get_error_message()
                ];
            }

            $code = wp_remote_retrieve_response_code($response);
            if ($code !== 200) {
                return [
                    'status' => self::STATUS_WARNING,
                    'message' => "API returned status code: {$code}"
                ];
            }

            return [
                'status' => self::STATUS_OK,
                'message' => 'API connection successful'
            ];
        } catch (\Exception $e) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => $e->getMessage()
            ];
        }
    }

    private function checkWooCommerce(): array
    {
        if (!class_exists('WooCommerce')) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => 'WooCommerce is not active'
            ];
        }

        return [
            'status' => self::STATUS_OK,
            'message' => 'WooCommerce is active and configured'
        ];
    }

    private function checkBackgroundProcessing(): array
    {
        $processes = [
            'product_sync' => 'wpwps_product_sync',
            'order_sync' => 'wpwps_order_sync'
        ];

        $results = [];
        foreach ($processes as $name => $action) {
            $count = wp_count_posts("async-request-{$action}");
            if ($count->pending > 100) {
                $results[$name] = [
                    'status' => self::STATUS_WARNING,
                    'message' => "Large queue: {$count->pending} items pending"
                ];
            } else {
                $results[$name] = [
                    'status' => self::STATUS_OK,
                    'message' => "Queue size: {$count->pending}"
                ];
            }
        }

        return $results;
    }

    private function checkDatabase(): array
    {
        global $wpdb;

        try {
            $wpdb->get_results("SELECT 1");
            return [
                'status' => self::STATUS_OK,
                'message' => 'Database connection successful'
            ];
        } catch (\Exception $e) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => $e->getMessage()
            ];
        }
    }

    private function checkFileSystem(): array
    {
        $uploadDir = wp_upload_dir();
        $testFile = $uploadDir['basedir'] . '/wpwps-test.txt';

        try {
            // Test write
            file_put_contents($testFile, 'test');
            
            // Test read
            $content = file_get_contents($testFile);
            
            // Clean up
            unlink($testFile);

            if ($content !== 'test') {
                return [
                    'status' => self::STATUS_WARNING,
                    'message' => 'File content verification failed'
                ];
            }

            return [
                'status' => self::STATUS_OK,
                'message' => 'Filesystem operations successful'
            ];
        } catch (\Exception $e) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => $e->getMessage()
            ];
        }
    }
}