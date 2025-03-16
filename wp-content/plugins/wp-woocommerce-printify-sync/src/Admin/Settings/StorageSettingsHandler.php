<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Settings;

class StorageSettingsHandler
{
    private StorageManager $storage;
    private EncryptionService $encryption;
    private LoggerInterface $logger;

    public function __construct(
        StorageManager $storage,
        EncryptionService $encryption,
        LoggerInterface $logger
    ) {
        $this->storage = $storage;
        $this->encryption = $encryption;
        $this->logger = $logger;
    }

    public function handle(): void
    {
        add_action('wp_ajax_test_storage_connection', [$this, 'testConnection']);
        add_action('wp_ajax_save_storage_settings', [$this, 'saveSettings']);
    }

    public function testConnection(): void
    {
        try {
            if (!check_ajax_referer('printify_nonce', 'nonce', false)) {
                throw new \Exception('Invalid nonce');
            }

            $provider = sanitize_text_field($_POST['provider'] ?? '');
            if (empty($provider)) {
                throw new \Exception('Provider not specified');
            }

            $result = $this->storage->testConnection($provider);

            wp_send_json_success([
                'success' => $result,
                'message' => $result 
                    ? __('Connection successful!', 'wp-woocommerce-printify-sync')
                    : __('Connection failed', 'wp-woocommerce-printify-sync')
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Storage connection test failed', [
                'error' => $e->getMessage()
            ]);

            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    public function saveSettings(): void
    {
        try {
            if (!check_ajax_referer('printify_nonce', 'nonce', false)) {
                throw new \Exception('Invalid nonce');
            }

            $data = $_POST['data'] ?? [];
            if (empty($data)) {
                throw new \Exception('No settings data provided');
            }

            // Encrypt sensitive data
            foreach ($data as $key => $value) {
                if ($this->isEncryptedField($key)) {
                    $data[$key] = $this->encryption->encrypt($value);
                }
            }

            // Save settings
            update_option('printify_storage_settings', $data);

            wp_send_json_success([
                'message' => __('Settings saved successfully', 'wp-woocommerce-printify-sync')
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Save storage settings failed', [
                'error' => $e->getMessage()
            ]);

            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    private function isEncryptedField(string $key): bool
    {
        return in_array($key, [
            'google_drive_client_secret',
            'google_drive_refresh_token',
            'r2_access_key_id',
            'r2_secret_access_key'
        ]);
    }
}