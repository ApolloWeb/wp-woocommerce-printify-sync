<?php
/**
 * Core Plugin Class
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Core
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Core;

use ApolloWeb\WPWooCommercePrintifySync\API;
use ApolloWeb\WPWooCommercePrintifySync\Settings;
use ApolloWeb\WPWooCommercePrintifySync\Admin;
use WP_Error;

/**
 * Main plugin class that handles core functionality
 */
final class PluginCore {
    /**
     * Plugin instance
     *
     * @var self
     */
    private static $instance = null;
    
    /**
     * Admin menu handler
     *
     * @var Admin\MenuManager
     */
    private $menuManager;
    
    /**
     * Asset manager
     *
     * @var Admin\AssetManager
     */
    private $assetManager;
    
    /**
     * Data provider
     *
     * @var DataProvider
     */
    private $dataProvider;
    
    /**
     * Get the plugin instance
     *
     * @return self Plugin instance
     */
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->menuManager = new Admin\MenuManager();
        $this->assetManager = new Admin\AssetManager();
        $this->dataProvider = new DataProvider();
        
        // Initialize components
        $this->initComponents();
    }
    
    /**
     * Initialize plugin components
     *
     * @return void
     */
    private function initComponents(): void {
        // Register admin menus
        add_action('admin_menu', [$this->menuManager, 'registerPluginMenus']);
        
        // Register assets
        add_action('admin_enqueue_scripts', [$this->assetManager, 'enqueueAssets']);
        
        // Register REST API endpoints
        add_action('rest_api_init', [$this, 'registerRestApi']);
        
        // Register AJAX handlers
        $this->registerAjaxHandlers();
    }

    /**
     * Register AJAX handlers
     *
     * @return void
     */
    public function registerAjaxHandlers(): void {
        // Printify API handlers
        add_action('wp_ajax_wpwps_test_connection', [$this, 'ajaxTestConnection']);
        add_action('wp_ajax_wpwps_save_settings', [$this, 'ajaxSaveSettings']);
        add_action('wp_ajax_wpwps_test_chatgpt', [$this, 'ajaxTestChatGpt']);
        
        // Add action for handling errors
        add_action('wp_ajax_nopriv_wpwps_ajax', [$this, 'handleUnauthorizedAccess']);
    }
    
    /**
     * Register REST API endpoints
     *
     * @return void
     */
    public function registerRestApi(): void {
        $api = new API\APIController();
        $api->registerRoutes();
    }

    /**
     * Handle unauthorized AJAX access
     *
     * @return void
     */
    public function handleUnauthorizedAccess(): void {
        wp_send_json_error([
            'message' => __('You do not have permission to perform this action.', 'wp-woocommerce-printify-sync'),
            'code' => 'unauthorized'
        ], 403);
    }

    /**
     * Verify AJAX request nonce
     *
     * @param string $nonce Nonce to verify
     * @return bool Whether nonce is valid
     */
    private function verifyNonce(?string $nonce): bool {
        return !empty($nonce) && wp_verify_nonce($nonce, 'wpwps-settings-nonce');
    }

    /**
     * AJAX handler for testing Printify API connection
     *
     * @return void
     */
    public function ajaxTestConnection(): void {
        // Check for CSRF protection
        if (!$this->verifyNonce($_POST['nonce'] ?? '')) {
            wp_send_json_error([
                'message' => __('Security check failed', 'wp-woocommerce-printify-sync'),
                'code' => 'invalid_nonce'
            ], 401);
            return;
        }
        
        try {
            $apiKey = sanitize_text_field($_POST['api_key'] ?? '');
            $endpoint = esc_url_raw($_POST['endpoint'] ?? '');
            
            // Validate required parameters
            if (empty($apiKey) || empty($endpoint)) {
                wp_send_json_error([
                    'message' => __('API key and endpoint are required', 'wp-woocommerce-printify-sync'),
                    'code' => 'missing_parameters'
                ], 400);
                return;
            }
            
            // Use dependency injection pattern
            $controller = $this->getSettingsController();
            $result = $controller->testPrintifyConnection($apiKey, $endpoint);
            if (is_wp_error($result)) {
                $this->handleApiError($result);
                return;
            }
            
            // Log success and send response
            $this->logSuccess('Connection successful', [
                'shops_count' => count($result)
            ]);
            
            wp_send_json_success([
                'shops' => $result,
                'message' => sprintf(
                    __('Successfully connected. Found %d shops.', 'wp-woocommerce-printify-sync'), 
                    count($result)
                ),
            ]);
        } catch (\Exception $e) {
            $this->handleException($e, 'api_connection');
        }
    }

    /**
     * Get settings controller instance
     *
     * @return Settings\SettingsController
     */
    private function getSettingsController(): Settings\SettingsController {
        return new Settings\SettingsController();
    }

    /**
     * Handle API errors in a consistent way
     *
     * @param WP_Error $error WordPress error
     * @return void
     */
    private function handleApiError(WP_Error $error): void {
        wp_send_json_error([
            'message' => $error->get_error_message(),
            'code' => $error->get_error_code() ?: 'api_error'
        ], 400);
    }

    /**
     * Handle exceptions in a consistent way
     *
     * @param \Exception $exception The caught exception
     * @param string $context Context where the exception occurred
     * @return void
     */
    private function handleException(\Exception $exception, string $context = 'general'): void {
        // Log detailed error for debugging
        error_log(sprintf(
            '%s Error: %s in %s on line %d',
            $context,
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine()
        ));
        
        wp_send_json_error([
            'message' => __('An unexpected error occurred.', 'wp-woocommerce-printify-sync'),
            'code' => 'exception',
            'context' => $context,
            'details' => WP_DEBUG ? $exception->getMessage() : null
        ], 500);
    }

    /**
     * Log successful operations for monitoring
     *
     * @param string $message Success message
     * @param array $data Additional data to log
     * @return void
     */
    private function logSuccess(string $message, array $data = []): void {
        if (WP_DEBUG) {
            error_log(sprintf(
                'WPWPS Success: %s | Data: %s',
                $message,
                json_encode($data)
            ));
        }
    }

    /**
     * AJAX handler for saving plugin settings
     *
     * @return void
     */
    public function ajaxSaveSettings(): void {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wpwps-settings-nonce')) {
            wp_send_json_error(['message' => __('Security check failed', 'wp-woocommerce-printify-sync')]);
        }

        // Parse the form data
        parse_str($_POST['settings'], $settings);

        // Use our Settings controller to save settings
        $controller = new Settings\SettingsController();
        $result = $controller->saveSettings($settings);
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }
        
        wp_send_json_success(['message' => __('Settings saved successfully', 'wp-woocommerce-printify-sync')]);
    }

    /**
     * AJAX handler for testing ChatGPT API
     *
     * @return void
     */
    public function ajaxTestChatGpt(): void {
        // Check for CSRF protection
        if (!$this->verifyNonce($_POST['nonce'] ?? '')) {
            wp_send_json_error([
                'message' => __('Security check failed', 'wp-woocommerce-printify-sync'),
                'code' => 'invalid_nonce'
            ], 401);
            return;
        }
        
        try {
            $apiKey = sanitize_text_field($_POST['chatgpt_api_key'] ?? '');
            $temperature = floatval($_POST['temperature'] ?? 0.7);
            
            if (empty($apiKey)) {
                wp_send_json_error([
                    'message' => __('ChatGPT API key is required', 'wp-woocommerce-printify-sync'),
                    'code' => 'missing_parameters'
                ], 400);
                return;
            }
            // Use dedicated API handler
            $chatGptHandler = $this->getChatGptHandler($apiKey, $temperature);
            $result = $chatGptHandler->testConnection();
            if (is_wp_error($result)) {
                $this->handleApiError($result);
                return;
            }
            
            // Log success
            $this->logSuccess('ChatGPT connection successful', [
                'estimated_cost' => $result['estimated_cost'],
                'tokens' => $result['tokens_per_month']
            ]);
            
            wp_send_json_success([
                'estimate' => $result,
                'message' => __('ChatGPT connection successful!', 'wp-woocommerce-printify-sync')
            ]);
        } catch (\Exception $e) {
            $this->handleException($e, 'chatgpt_api');
        }
    }

    /**
     * Get ChatGPT API handler instance with dependency injection support
     * 
     * @param string $apiKey API key
     * @param float $temperature Temperature setting
     * @return API\ChatGPTHandler API handler instance
     */
    private function getChatGptHandler(string $apiKey, float $temperature): API\ChatGPTHandler {
        // Allow plugins to override the API handler implementation
        $handlerClass = apply_filters('wpwps_chatgpt_handler_class', API\ChatGPTHandler::class);
        
        // Create and configure the handler
        $handler = new $handlerClass($apiKey, $temperature);
            
        // Allow plugins to modify the handler instance
        return apply_filters('wpwps_chatgpt_handler_instance', $handler, $apiKey, $temperature);
    }
}
