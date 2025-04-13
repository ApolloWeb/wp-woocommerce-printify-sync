<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Webhooks;

use ApolloWeb\WPWooCommercePrintifySync\Api\PrintifyApiInterface;
use ApolloWeb\WPWooCommercePrintifySync\Logger\LoggerInterface;
use ApolloWeb\WPWooCommercePrintifySync\Settings\SettingsServiceInterface;
use ApolloWeb\WPWooCommercePrintifySync\Products\ImportScheduler;

/**
 * Service for managing webhooks and their health
 */
class WebhookService {
    /**
     * @var PrintifyApiInterface
     */
    private $api;
    
    /**
     * @var LoggerInterface
     */
    private $logger;
    
    /**
     * @var SettingsServiceInterface
     */
    private $settings;
    
    /**
     * @var ImportScheduler
     */
    private $import_scheduler;
    
    /**
     * @var array Required webhooks
     */
    private $required_webhooks = [
        'product.update',
        'product.delete',
        'order.created', 
        'order.update',
        'shipping.update'
    ];
    
    /**
     * Constructor
     *
     * @param PrintifyApiInterface $api
     * @param LoggerInterface $logger
     * @param SettingsServiceInterface $settings
     * @param ImportScheduler $import_scheduler
     */
    public function __construct(
        PrintifyApiInterface $api,
        LoggerInterface $logger, 
        SettingsServiceInterface $settings,
        ImportScheduler $import_scheduler
    ) {
        $this->api = $api;
        $this->logger = $logger;
        $this->settings = $settings;
        $this->import_scheduler = $import_scheduler;
    }
    
    /**
     * Register all required webhooks with Printify
     *
     * @return bool Success status
     */
    public function register_webhooks() {
        $printify_settings = $this->settings->getPrintifySettings();
        
        if (empty($printify_settings['api_key']) || empty($printify_settings['shop_id'])) {
            $this->logger->log_error('webhooks', 'Cannot register webhooks - API key or shop ID missing');
            return false;
        }
        
        $webhook_url = rest_url('wpwps/v1/webhook');
        $registered = [];
        $errors = 0;
        
        // Get existing webhooks
        $existing_webhooks = $this->api->get_webhooks();
        $existing_events = [];
        
        if (!is_wp_error($existing_webhooks)) {
            foreach ($existing_webhooks as $webhook) {
                if (isset($webhook['event']) && isset($webhook['url']) && $webhook['url'] === $webhook_url) {
                    $existing_events[] = $webhook['event'];
                }
            }
        }
        
        // Register missing webhooks
        foreach ($this->required_webhooks as $event) {
            if (!in_array($event, $existing_events)) {
                $result = $this->api->register_webhook($event, $webhook_url);
                
                if (is_wp_error($result)) {
                    $this->logger->log_error(
                        'webhooks', 
                        sprintf('Failed to register webhook for %s: %s', $event, $result->get_error_message())
                    );
                    $errors++;
                } else {
                    $registered[] = $event;
                    $this->logger->log_success(
                        'webhooks',
                        sprintf('Registered webhook for %s', $event)
                    );
                }
            } else {
                $this->logger->log_info(
                    'webhooks',
                    sprintf('Webhook for %s already registered', $event)
                );
            }
        }
        
        // Store last webhook registration time and count
        update_option('wpwps_webhooks_last_registered', current_time('mysql'));
        update_option('wpwps_webhooks_registered_count', count($registered));
        update_option('wpwps_webhooks_error_count', $errors);
        
        $this->logger->log_info(
            'webhooks',
            sprintf('Webhook registration complete: %d registered, %d errors', count($registered), $errors)
        );
        
        return $errors === 0;
    }
    
    /**
     * Check webhook health and run catchup sync if needed
     *
     * @return bool True if webhooks are healthy, false if not
     */
    public function check_health() {
        // Check if the initial import has run
        $initial_import_complete = get_option('wpwps_initial_import_complete', false);
        
        if (!$initial_import_complete) {
            $this->logger->log_warning(
                'webhook_health',
                'Initial import not complete, skipping webhook health check'
            );
            return false;
        }
        
        // Check when we last received a webhook (stored in option)
        $last_webhook = get_option('wpwps_last_webhook_received');
        $webhook_timeout = 24 * HOUR_IN_SECONDS; // 24 hours
        
        // Check registration state
        $webhooks = $this->api->get_webhooks();
        $registered_webhooks = [];
        $webhook_url = rest_url('wpwps/v1/webhook');
        
        if (!is_wp_error($webhooks)) {
            foreach ($webhooks as $webhook) {
                if (isset($webhook['url']) && $webhook['url'] === $webhook_url) {
                    $registered_webhooks[] = $webhook['event'];
                }
            }
        }
        
        // Compare with required webhooks
        $missing_webhooks = array_diff($this->required_webhooks, $registered_webhooks);
        
        $webhooks_healthy = true;
        $needs_catchup = false;
        
        // Log status
        if (!empty($missing_webhooks)) {
            $this->logger->log_warning(
                'webhook_health',
                sprintf('Missing webhooks: %s', implode(', ', $missing_webhooks))
            );
            $webhooks_healthy = false;
            $needs_catchup = true;
        }
        
        // Check for webhook timeout
        if (!$last_webhook || (strtotime($last_webhook) < (time() - $webhook_timeout))) {
            $this->logger->log_warning(
                'webhook_health',
                'No webhooks received in the last 24 hours'
            );
            $webhooks_healthy = false;
            $needs_catchup = true;
        }
        
        // If webhooks are unhealthy, re-register and perform catchup sync
        if (!$webhooks_healthy) {
            $this->logger->log_info('webhook_health', 'Re-registering webhooks');
            $this->register_webhooks();
            
            if ($needs_catchup) {
                $this->logger->log_info('webhook_health', 'Running catchup sync');
                
                // Schedule a catchup sync with "smart" flag that will only update changed products
                $this->import_scheduler->start_catchup_sync();
                
                update_option('wpwps_last_catchup_sync', current_time('mysql'));
            }
        } else {
            $this->logger->log_info('webhook_health', 'Webhooks are healthy');
        }
        
        update_option('wpwps_webhooks_healthy', $webhooks_healthy ? 'yes' : 'no');
        return $webhooks_healthy;
    }
    
    /**
     * Record webhook received
     *
     * @param string $event Webhook event type
     * @return void
     */
    public function record_webhook_received($event) {
        update_option('wpwps_last_webhook_received', current_time('mysql'));
        update_option('wpwps_last_webhook_event', $event);
        
        // Increment webhook counter
        $count = get_option('wpwps_webhook_count', 0);
        update_option('wpwps_webhook_count', $count + 1);
    }
}
