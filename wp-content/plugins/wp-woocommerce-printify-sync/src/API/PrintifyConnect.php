<?php
/**
 * Printify connection manager.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\API
 */

namespace ApolloWeb\WPWooCommercePrintifySync\API;

use ApolloWeb\WPWooCommercePrintifySync\Services\Logger;
use ApolloWeb\WPWooCommercePrintifySync\Services\EncryptionService;

/**
 * Handles OAuth connection with Printify.
 */
class PrintifyConnect
{
    /**
     * Logger instance.
     *
     * @var Logger
     */
    private $logger;
    
    /**
     * Encryption service.
     *
     * @var EncryptionService
     */
    private $encryption;
    
    /**
     * Constructor.
     *
     * @param Logger           $logger     Logger instance.
     * @param EncryptionService $encryption Encryption service.
     */
    public function __construct(Logger $logger, EncryptionService $encryption)
    {
        $this->logger = $logger;
        $this->encryption = $encryption;
    }
    
    /**
     * Register hooks.
     */
    public function init()
    {
        add_action('admin_post_wpwps_printify_connect', [$this, 'handleOAuthRedirect']);
        add_action('admin_post_wpwps_printify_disconnect', [$this, 'handleDisconnect']);
    }
    
    /**
     * Get OAuth URL.
     *
     * @return string OAuth URL.
     */
    public function getOAuthUrl()
    {
        $client_id = get_option('wpwps_printify_client_id', '');
        
        if (empty($client_id)) {
            return '';
        }
        
        $redirect_uri = admin_url('admin-post.php?action=wpwps_printify_connect');
        $state = wp_create_nonce('wpwps_printify_oauth');
        
        return add_query_arg([
            'client_id' => $client_id,
            'redirect_uri' => $redirect_uri,
            'response_type' => 'code',
            'scope' => 'shops.read orders.read orders.write products.read products.write webhooks.write',
            'state' => $state,
        ], 'https://accounts.printify.com/oauth/authorize');
    }
    
    /**
     * Handle OAuth redirect.
     */
    public function handleOAuthRedirect()
    {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have permission to do this.', 'wp-woocommerce-printify-sync'));
        }
        
        $code = isset($_GET['code']) ? sanitize_text_field($_GET['code']) : '';
        $state = isset($_GET['state']) ? sanitize_text_field($_GET['state']) : '';
        
        if (empty($code) || empty($state) || !wp_verify_nonce($state, 'wpwps_printify_oauth')) {
            wp_redirect(admin_url('admin.php?page=wpwps-settings&error=invalid_request'));
            exit;
        }
        
        $client_id = get_option('wpwps_printify_client_id', '');
        $client_secret = $this->encryption->getKey('wpwps_printify_client_secret');
        
        if (empty($client_id) || empty($client_secret)) {
            wp_redirect(admin_url('admin.php?page=wpwps-settings&error=missing_credentials'));
            exit;
        }
        
        $response = wp_remote_post('https://accounts.printify.com/oauth/token', [
            'body' => [
                'client_id' => $client_id,
                'client_secret' => $client_secret,
                'code' => $code,
                'grant_type' => 'authorization_code',
                'redirect_uri' => admin_url('admin-post.php?action=wpwps_printify_connect'),
            ],
            'timeout' => 30,
        ]);
        
        if (is_wp_error($response)) {
            $this->logger->error('OAuth error: ' . $response->get_error_message());
            wp_redirect(admin_url('admin.php?page=wpwps-settings&error=oauth_error'));
            exit;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (empty($body['access_token'])) {
            $this->logger->error('OAuth error: No access token received', ['response' => $body]);
            wp_redirect(admin_url('admin.php?page=wpwps-settings&error=no_access_token'));
            exit;
        }
        
        // Store access token securely
        $this->encryption->storeKey('wpwps_printify_access_token', $body['access_token']);
        
        if (!empty($body['refresh_token'])) {
            $this->encryption->storeKey('wpwps_printify_refresh_token', $body['refresh_token']);
        }
        
        // Store token expiration
        if (!empty($body['expires_in'])) {
            update_option('wpwps_printify_token_expires', time() + $body['expires_in']);
        }
        
        // Get user shops with new token
        $user_shops = $this->getUserShops($body['access_token']);
        
        if (!empty($user_shops)) {
            update_option('wpwps_printify_shops', $user_shops);
        }
        
        wp_redirect(admin_url('admin.php?page=wpwps-settings&success=connected'));
        exit;
    }
    
    /**
     * Handle disconnect action.
     */
    public function handleDisconnect()
    {
        if (!current_user_can('manage_woocommerce') || !wp_verify_nonce($_GET['_wpnonce'] ?? '', 'wpwps_printify_disconnect')) {
            wp_die(__('You do not have permission to do this.', 'wp-woocommerce-printify-sync'));
        }
        
        // Clear OAuth tokens
        $this->encryption->deleteKey('wpwps_printify_access_token');
        $this->encryption->deleteKey('wpwps_printify_refresh_token');
        delete_option('wpwps_printify_token_expires');
        delete_option('wpwps_printify_shops');
        
        $this->logger->info('User disconnected from Printify');
        
        wp_redirect(admin_url('admin.php?page=wpwps-settings&success=disconnected'));
        exit;
    }
    
    /**
     * Check if token needs refresh and refresh if needed.
     * 
     * @return bool True if token valid, false otherwise
     */
    public function ensureValidToken()
    {
        $expires = get_option('wpwps_printify_token_expires', 0);
        
        // If token expires soon (in next 5 minutes), refresh it
        if ($expires < time() + 300) {
            return $this->refreshToken();
        }
        
        return true;
    }
    
    /**
     * Refresh access token.
     * 
     * @return bool True on success, false on failure
     */
    private function refreshToken()
    {
        $refresh_token = $this->encryption->getKey('wpwps_printify_refresh_token');
        
        if (empty($refresh_token)) {
            $this->logger->error('Cannot refresh token: No refresh token available');
            return false;
        }
        
        $client_id = get_option('wpwps_printify_client_id', '');
        $client_secret = $this->encryption->getKey('wpwps_printify_client_secret');
        
        if (empty($client_id) || empty($client_secret)) {
            $this->logger->error('Cannot refresh token: Missing client credentials');
            return false;
        }
        
        $response = wp_remote_post('https://accounts.printify.com/oauth/token', [
            'body' => [
                'client_id' => $client_id,
                'client_secret' => $client_secret,
                'refresh_token' => $refresh_token,
                'grant_type' => 'refresh_token',
            ],
            'timeout' => 30,
        ]);
        
        if (is_wp_error($response)) {
            $this->logger->error('Token refresh error: ' . $response->get_error_message());
            return false;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (empty($body['access_token'])) {
            $this->logger->error('Token refresh error: No access token received', ['response' => $body]);
            return false;
        }
        
        // Store new tokens
        $this->encryption->storeKey('wpwps_printify_access_token', $body['access_token']);
        
        if (!empty($body['refresh_token'])) {
            $this->encryption->storeKey('wpwps_printify_refresh_token', $body['refresh_token']);
        }
        
        // Update expiration
        if (!empty($body['expires_in'])) {
            update_option('wpwps_printify_token_expires', time() + $body['expires_in']);
        }
        
        $this->logger->info('Printify access token refreshed successfully');
        
        return true;
    }
    
    /**
     * Get user's shops with access token.
     * 
     * @param string $access_token Access token.
     * @return array User shops.
     */
    private function getUserShops($access_token)
    {
        $response = wp_remote_get('https://api.printify.com/v1/shops.json', [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Accept' => 'application/json;version=1',
            ],
            'timeout' => 30,
        ]);
        
        if (is_wp_error($response)) {
            $this->logger->error('Error fetching shops: ' . $response->get_error_message());
            return [];
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (empty($body['shops'])) {
            return [];
        }
        
        return array_map(function($shop) {
            return [
                'id' => $shop['id'],
                'title' => $shop['title'],
                'type' => $shop['type'] ?? 'unknown',
            ];
        }, $body['shops']);
    }
    
    /**
     * Get a valid access token.
     * 
     * @return string|bool Access token or false if not available
     */
    public function getAccessToken()
    {
        if (!$this->ensureValidToken()) {
            return false;
        }
        
        return $this->encryption->getKey('wpwps_printify_access_token');
    }
}
