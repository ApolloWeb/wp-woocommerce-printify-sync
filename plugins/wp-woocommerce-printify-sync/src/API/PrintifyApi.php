<?php
namespace ApolloWeb\WPWooCommercePrintifySync\API;

use ApolloWeb\WPWooCommercePrintifySync\Logger\Logger;

class PrintifyApi {
    protected string $apiKey;
    protected string $endpoint;
    protected int $maxRetries = 5;
    protected int $initialDelay = 1; // seconds
    protected Logger $logger;
    
    public function __construct(string $apiKey, string $endpoint = 'https://api.printify.com/v1', ?Logger $logger = null) {
        $this->apiKey   = $apiKey;
        $this->endpoint = rtrim($endpoint, '/');
        $this->logger   = $logger ?? new Logger();
    }
    
    protected function request(string $path, array $args = []): array {
        $url = $this->endpoint . $path;
        $default_args = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type'  => 'application/json',
            ],
            'timeout' => 30,
        ];
        $args = array_merge($default_args, $args);
        
        $attempt = 0;
        $delay = $this->initialDelay;
        do {
            $attempt++;
            $response = wp_remote_get($url, $args);
            
            if (!is_wp_error($response)) {
                $status = wp_remote_retrieve_response_code($response);
                if ($status !== 429 && $status < 500) {
                    break;
                }
            }
            
            $this->logger->info("Printify API request failed on attempt {$attempt}. Retrying in {$delay} seconds.");
            sleep($delay);
            $delay *= 2; // Exponential backoff.
        } while ($attempt < $this->maxRetries);
        
        if (is_wp_error($response)) {
            $this->logger->error("Printify API request failed: " . $response->get_error_message());
            return [];
        }
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        return is_array($data) ? $data : [];
    }
    
    public function getShops(): array {
        return $this->request('/shops');
    }
    
    // Future methods can be added here.
}
