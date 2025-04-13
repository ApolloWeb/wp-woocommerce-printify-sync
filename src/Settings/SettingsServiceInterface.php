<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Settings;

/**
 * Settings Service Interface
 */
interface SettingsServiceInterface {
    /**
     * Test Printify API connection
     * 
     * @param string $api_key
     * @param string $api_endpoint
     * @return array|\WP_Error
     */
    public function testPrintifyConnection($api_key, $api_endpoint);
    
    /**
     * Save Printify settings
     * 
     * @param string $api_key
     * @param string $api_endpoint
     * @param string $shop_id
     * @return bool|\WP_Error
     */
    public function savePrintifySettings($api_key, $api_endpoint, $shop_id);
    
    /**
     * Get Printify settings
     * 
     * @return array
     */
    public function getPrintifySettings();
    
    /**
     * Test ChatGPT API connection
     * 
     * @param string $api_key
     * @return bool|\WP_Error
     */
    public function testChatGptConnection($api_key);
    
    /**
     * Save ChatGPT settings
     * 
     * @param string $api_key
     * @param int $monthly_cap
     * @param int $token_limit
     * @param float $temperature
     * @return bool|\WP_Error
     */
    public function saveChatGptSettings($api_key, $monthly_cap, $token_limit, $temperature);
    
    /**
     * Get ChatGPT settings
     * 
     * @return array
     */
    public function getChatGptSettings();
}
