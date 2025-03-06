<?php
/**
 * API Manager class for handling API connections
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\API
 */

namespace ApolloWeb\WPWooCommercePrintifySync\API;

use ApolloWeb\WPWooCommercePrintifySync\Utility\Logger;
use ApolloWeb\WPWooCommercePrintifySync\Utility\CredentialManager;

class ApiManager {
    /**
     * @var Logger
     */
    private $logger;
    
    /**
     * @var CredentialManager
     */
    private $credentialManager;
    
    /**
     * @var PrintifyApi
     */
    private $printifyApi;
    
    /**
     * @var GeolocationApi
     */
    private $geolocationApi;
    
    /**
     * @var CurrencyApi
     */
    private $currencyApi;
    
    /**
     * @var string
     */
    private $apiMode;

    /**
     * Constructor
     *
     * @param Logger $logger Logger instance
     */
    public function __construct(Logger $logger) {
        $this->logger = $logger;
        $this->credentialManager = new CredentialManager();
        $this->apiMode = get_option('wpwprintifysync_api_mode', 'production');
        
        $this->initApis();
    }
    
    /**
     * Initialize API clients
     */
    private function initApis() {
        // Initialize Printify API
        $printifyApiKey = $this->credentialManager->getDecryptedValue('wpwprintifysync_printify_api_key');
        $this->printifyApi = new PrintifyApi($printifyApiKey, $this->apiMode, $this->logger);
        
        // Initialize Geolocation API
        $geolocationApiKey = $this->credentialManager->getDecryptedValue('wpwprintifysync_geolocation_api_key');
        $this->geolocationApi = new GeolocationApi($geolocationApiKey, $this->apiMode, $this->logger);
        
        // Initialize Currency API
        $currencyApiKey = $this->credentialManager->getDecryptedValue('wpwprintifysync_currency_api_key');
        $this->currencyApi = new CurrencyApi($currencyApiKey, $this->apiMode, $this->logger);
    }
    
    /**
     * Get Printify API instance
     *
     * @return PrintifyApi
     */
    public function getPrintifyApi() {
        return $this->printifyApi;
    }
    
    /**
     * Get Geolocation API instance
     *
     * @return GeolocationApi
     */
    public function getGeolocationApi() {
        return $this->geolocationApi;
    }
    
    /**
     * Get Currency API instance
     *
     * @return CurrencyApi
     */
    public function getCurrencyApi() {
        return $this->currencyApi;
    }
    
    /**
     * Update API mode
     *
     * @param string $mode API mode (production or development)
     */
    public function updateApiMode($mode) {
        if (!in_array($mode, ['production', 'development'])) {
            throw new \InvalidArgumentException("Invalid API mode: {$mode}");
        }
        
        $this->apiMode = $mode;
        update_option('wpwprintifysync_api_mode', $mode);
        
        // Reinitialize APIs with new mode
        $this->initApis();
        
        $this->logger->info("API mode updated", [
            'mode' => $mode,
            'time' => current_time('mysql')
        ]);
    }
    
    /**
     * Validate webhook request
     *
     * @return bool
     */
    public function validateWebhookRequest() {
        $signature = isset($_SERVER['HTTP_X_PRINTIFY_SIGNATURE']) ? 
            $_SERVER['HTTP_X_PRINTIFY_SIGNATURE'] : '';
            
        if (empty($signature)) {
            $this->logger->error('Missing webhook signature', [
                'headers' => getallheaders()
            ]);
            return false;
        }
        
        // Get raw post data
        $payload = file_get_contents('php://input');
        
        // Get webhook secret
        $secret = $this->credentialManager->getDecryptedValue('wpwprintifysync_webhook_secret');
        
        // Generate expected signature
        $expectedSignature = hash_hmac('sha256', $payload, $secret);
        
        if ($signature !== $expectedSignature) {
            $this->logger->error('Invalid webhook signature', [
                'provided' => $signature,
                'expected' => $expectedSignature
            ]);
            return false;
        }
        
        return true;
    }
    
    /**
     * Test API connection
     *
     * @param string $apiType API type (printify, geolocation, currency)
     * @param string $apiKey API key to test
     * @return array Test result
     */
    public function testApiConnection($apiType, $apiKey = null) {
        switch ($apiType) {
            case 'printify':
                $api = ($apiKey !== null) ? 
                    new PrintifyApi($apiKey, $this->apiMode, $this->logger) : 
                    $this->printifyApi;
                return $api->testConnection();
                
            case 'geolocation':
                $api = ($apiKey !== null) ? 
                    new GeolocationApi($apiKey, $this->apiMode, $this->logger) : 
                    $this->geolocationApi;
                return $api->testConnection();
                
            case 'currency':
                $api = ($apiKey !== null) ? 
                    new CurrencyApi($apiKey, $this->apiMode, $this->logger) : 
                    $this->currencyApi;
                return $api->testConnection();
                
            default:
                throw new \InvalidArgumentException("Invalid API type: {$apiType}");
        }
    }
}