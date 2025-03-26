<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Providers;

use ApolloWeb\WPWooCommercePrintifySync\Core\ServiceProvider;
use ApolloWeb\WPWooCommercePrintifySync\Core\PrintifyClient;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use phpseclib3\Crypt\AES;

class SettingsProvider extends ServiceProvider
{
    private const OPTION_PREFIX = 'wpwps_';
    private const DEFAULT_API_ENDPOINT = 'https://api.printify.com/v1/';
    private Client $client;

    public function boot(): void
    {
        $this->client = new Client([
            'base_uri' => 'https://api.printify.com/v1/',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->getDecryptedApiKey(),
                'Accept' => 'application/json',
            ]
        ]);

        $this->registerAdminMenu(
            'WC Printify Settings',
            'Settings',
            'manage_options',
            'wpwps-settings',
            [$this, 'renderSettingsPage']
        );

        $this->registerAjaxEndpoint('wpwps_test_connection', [$this, 'testConnection']);
        $this->registerAjaxEndpoint('wpwps_save_settings', [$this, 'saveSettings']);
    }

    public function renderSettingsPage(): void
    {
        $data = [
            'api_key' => $this->getDecryptedApiKey(),
            'api_endpoint' => get_option(self::OPTION_PREFIX . 'api_endpoint', self::DEFAULT_API_ENDPOINT),
            'shop_id' => get_option(self::OPTION_PREFIX . 'shop_id'),
            'shops' => $this->getShops(),
            'gpt_api_key' => $this->getDecryptedGptKey(),
            'gpt_settings' => get_option(self::OPTION_PREFIX . 'gpt_settings', [
                'token_limit' => 2000,
                'temperature' => 0.7,
                'spend_cap' => 50
            ])
        ];

        echo $this->view->render('wpwps-settings', $data);
    }

    public function testConnection(): void
    {
        if (!$this->verifyNonce()) {
            return;
        }

        try {
            $response = $this->client->get('shops.json');
            $shops = json_decode($response->getBody()->getContents(), true);
            wp_send_json_success(['shops' => $shops]);
        } catch (GuzzleException $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    public function saveSettings(): void
    {
        if (!$this->verifyNonce()) {
            return;
        }

        $api_key = sanitize_text_field($_POST['api_key'] ?? '');
        $shop_id = sanitize_text_field($_POST['shop_id'] ?? '');
        $gpt_key = sanitize_text_field($_POST['gpt_api_key'] ?? '');
        $gpt_settings = array_map('sanitize_text_field', $_POST['gpt_settings'] ?? []);
        $api_endpoint = esc_url_raw($_POST['api_endpoint'] ?? self::DEFAULT_API_ENDPOINT);

        if ($api_key) {
            $this->saveEncryptedApiKey($api_key);
        }

        if ($gpt_key) {
            $this->saveEncryptedGptKey($gpt_key);
        }

        update_option(self::OPTION_PREFIX . 'shop_id', $shop_id);
        update_option(self::OPTION_PREFIX . 'gpt_settings', $gpt_settings);
        update_option(self::OPTION_PREFIX . 'api_endpoint', $api_endpoint);

        wp_send_json_success(['message' => 'Settings saved successfully']);
    }

    private function getShops(): array
    {
        try {
            $apiKey = get_option(self::OPTION_PREFIX . 'api_key');
            $apiEndpoint = get_option(self::OPTION_PREFIX . 'api_endpoint', self::DEFAULT_API_ENDPOINT);
            
            if (!$apiKey) {
                return [];
            }

            $client = new PrintifyClient($apiKey, 3, $apiEndpoint);
            $response = $client->get('shops.json');
            return $response['data'] ?? [];
        } catch (\Exception $e) {
            error_log('Failed to fetch Printify shops: ' . $e->getMessage());
            return [];
        }
    }

    private function getEncryptionKey(): string
    {
        $key = defined('WPWPS_ENCRYPTION_KEY') ? WPWPS_ENCRYPTION_KEY : AUTH_KEY;
        return substr(hash('sha256', $key), 0, 32);
    }

    private function encrypt(string $value): string
    {
        $cipher = new AES('gcm');
        $cipher->setKey($this->getEncryptionKey());
        return base64_encode($cipher->encrypt($value));
    }

    private function decrypt(string $value): string
    {
        $cipher = new AES('gcm');
        $cipher->setKey($this->getEncryptionKey());
        return $cipher->decrypt(base64_decode($value));
    }

    private function saveEncryptedApiKey(string $key): void
    {
        update_option(self::OPTION_PREFIX . 'api_key', $this->encrypt($key));
    }

    private function getDecryptedApiKey(): string
    {
        $encrypted = get_option(self::OPTION_PREFIX . 'api_key', '');
        return $encrypted ? $this->decrypt($encrypted) : '';
    }

    private function saveEncryptedGptKey(string $key): void
    {
        update_option(self::OPTION_PREFIX . 'gpt_key', $this->encrypt($key));
    }

    private function getDecryptedGptKey(): string
    {
        $encrypted = get_option(self::OPTION_PREFIX . 'gpt_key', '');
        return $encrypted ? $this->decrypt($encrypted) : '';
    }
}