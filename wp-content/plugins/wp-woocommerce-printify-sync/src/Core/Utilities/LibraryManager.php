<?php

namespace WPWPS\Core\Utilities;

use eftec\bladeone\BladeOne;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;

/**
 * Library Manager - Handles integration with BladeOne, GuzzleHTTP, and phpseclib
 *
 * @package WPWPS\Core\Utilities
 */
class LibraryManager {
    /**
     * @var BladeOne Instance of BladeOne template engine
     */
    private BladeOne $blade;

    /**
     * @var Client Instance of GuzzleHttp client
     */
    private Client $httpClient;

    /**
     * Initialize the library manager
     * 
     * @param string $templatesPath Path to templates directory
     * @param string $cachePath Path to cache directory
     * @param array $clientOptions Guzzle client options
     */
    public function __construct(
        string $templatesPath = '', 
        string $cachePath = '', 
        array $clientOptions = []
    ) {
        // Initialize BladeOne
        $this->initBlade($templatesPath, $cachePath);
        
        // Initialize Guzzle HTTP
        $this->initHttpClient($clientOptions);
    }
    
    /**
     * Initialize BladeOne template engine
     * 
     * @param string $templatesPath Path to templates directory
     * @param string $cachePath Path to cache directory
     * @return void
     */
    private function initBlade(string $templatesPath = '', string $cachePath = ''): void
    {
        if (empty($templatesPath)) {
            $templatesPath = WPWPS_PLUGIN_DIR . 'templates';
        }
        
        if (empty($cachePath)) {
            $cachePath = WPWPS_PLUGIN_DIR . 'cache/views';
        }
        
        // Create cache directory if it doesn't exist
        if (!file_exists($cachePath)) {
            wp_mkdir_p($cachePath);
        }
        
        // Create BladeOne instance with defined paths
        // Use MODE_AUTO for development and MODE_FAST for production
        $mode = defined('WP_DEBUG') && WP_DEBUG ? BladeOne::MODE_DEBUG : BladeOne::MODE_FAST;
        $this->blade = new BladeOne($templatesPath, $cachePath, $mode);
    }
    
    /**
     * Initialize GuzzleHttp client
     * 
     * @param array $clientOptions Guzzle client options
     * @return void
     */
    private function initHttpClient(array $clientOptions = []): void
    {
        // Default options for the HTTP client
        $defaultOptions = [
            RequestOptions::TIMEOUT => 30,
            RequestOptions::CONNECT_TIMEOUT => 10,
            RequestOptions::HTTP_ERRORS => true,
            RequestOptions::HEADERS => [
                'User-Agent' => 'WP-WooCommerce-Printify-Sync/' . WPWPS_VERSION,
                'Accept' => 'application/json',
            ],
        ];
        
        // Merge default options with custom options
        $options = array_merge($defaultOptions, $clientOptions);
        
        // Initialize the HTTP client
        $this->httpClient = new Client($options);
    }
    
    /**
     * Get BladeOne instance
     * 
     * @return BladeOne
     */
    public function getBlade(): BladeOne
    {
        return $this->blade;
    }
    
    /**
     * Get GuzzleHttp client instance
     * 
     * @return Client
     */
    public function getHttpClient(): Client
    {
        return $this->httpClient;
    }
    
    /**
     * Render a template using BladeOne
     * 
     * @param string $template Template name
     * @param array $data Data to pass to the template
     * @return string Rendered template
     */
    public function render(string $template, array $data = []): string
    {
        try {
            return $this->blade->run($template, $data);
        } catch (\Exception $e) {
            error_log('BladeOne template error: ' . $e->getMessage());
            return 'Error rendering template: ' . esc_html($e->getMessage());
        }
    }
    
    /**
     * Make an HTTP request using GuzzleHttp
     * 
     * @param string $method HTTP method
     * @param string $url URL to request
     * @param array $options Request options
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        return $this->httpClient->request($method, $url, $options);
    }
    
    /**
     * Generate a secure random string using phpseclib
     * 
     * @param int $length Length of the random string
     * @return string
     */
    public function generateSecureToken(int $length = 32): string
    {
        // Using phpseclib3 Random class to generate secure random bytes
        $random = new \phpseclib3\Crypt\Random();
        $bytes = $random->string($length);
        
        // Convert to hexadecimal for safety
        return bin2hex($bytes);
    }
}