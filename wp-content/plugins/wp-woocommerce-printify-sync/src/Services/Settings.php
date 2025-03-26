<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

use phpseclib3\Crypt\AES;

class Settings {
    private const ENCRYPTION_KEY = 'wpwps_encryption_key';
    private const PRINTIFY_API_KEY = 'wpwps_printify_api_key';
    private const PRINTIFY_API_ENDPOINT = 'wpwps_printify_api_endpoint';
    private const PRINTIFY_SHOP_ID = 'wpwps_printify_shop_id';
    private const CHATGPT_API_KEY = 'wpwps_chatgpt_api_key';
    private const CHATGPT_MONTHLY_CAP = 'wpwps_chatgpt_monthly_cap';
    private const CHATGPT_TOKENS = 'wpwps_chatgpt_tokens';
    private const CHATGPT_TEMPERATURE = 'wpwps_chatgpt_temperature';

    private $cipher;

    public function __construct() {
        $this->cipher = new AES('cbc');
        $this->initializeEncryptionKey();
    }

    private function initializeEncryptionKey(): void {
        if (!get_option(self::ENCRYPTION_KEY)) {
            $key = wp_generate_password(32, true, true);
            update_option(self::ENCRYPTION_KEY, $key);
        }
        $this->cipher->setKey(get_option(self::ENCRYPTION_KEY));
    }

    public function encrypt(string $data): string {
        return base64_encode($this->cipher->encrypt($data));
    }

    public function decrypt(string $data): string {
        return $this->cipher->decrypt(base64_decode($data));
    }

    public function getPrintifyApiKey(): ?string {
        $encrypted = get_option(self::PRINTIFY_API_KEY);
        return $encrypted ? $this->decrypt($encrypted) : null;
    }

    public function setPrintifyApiKey(string $key): void {
        update_option(self::PRINTIFY_API_KEY, $this->encrypt($key));
    }

    public function getPrintifyApiEndpoint(): string {
        return get_option(self::PRINTIFY_API_ENDPOINT, 'https://api.printify.com/v1');
    }

    public function setPrintifyApiEndpoint(string $endpoint): void {
        update_option(self::PRINTIFY_API_ENDPOINT, $endpoint);
    }

    public function getPrintifyShopId(): ?string {
        return get_option(self::PRINTIFY_SHOP_ID);
    }

    public function setPrintifyShopId(string $shopId): void {
        update_option(self::PRINTIFY_SHOP_ID, $shopId);
    }

    public function getChatGPTApiKey(): ?string {
        $encrypted = get_option(self::CHATGPT_API_KEY);
        return $encrypted ? $this->decrypt($encrypted) : null;
    }

    public function setChatGPTApiKey(string $key): void {
        update_option(self::CHATGPT_API_KEY, $this->encrypt($key));
    }

    public function getChatGPTSettings(): array {
        return [
            'monthly_cap' => get_option(self::CHATGPT_MONTHLY_CAP, 100),
            'tokens' => get_option(self::CHATGPT_TOKENS, 4000),
            'temperature' => get_option(self::CHATGPT_TEMPERATURE, 0.7)
        ];
    }

    public function setChatGPTSettings(array $settings): void {
        update_option(self::CHATGPT_MONTHLY_CAP, $settings['monthly_cap']);
        update_option(self::CHATGPT_TOKENS, $settings['tokens']);
        update_option(self::CHATGPT_TEMPERATURE, $settings['temperature']);
    }
}