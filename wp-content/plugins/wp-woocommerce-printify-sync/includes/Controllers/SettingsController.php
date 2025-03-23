<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Controllers;

class SettingsController {
    private $api;
    private $encryption;
    private $templating;

    public function __construct(
        PrintifyApi $api, 
        Encryption $encryption,
        Engine $templating
    ) {
        $this->api = $api;
        $this->encryption = $encryption;
        $this->templating = $templating;

        add_action('wp_ajax_wpps_test_printify', [$this, 'testPrintifyConnection']);
        add_action('wp_ajax_wpps_test_chatgpt', [$this, 'testChatGPTConnection']);
        add_action('wp_ajax_wpps_save_settings', [$this, 'saveSettings']);
    }

    public function render(): void {
        $data = [
            'api_key' => $this->encryption->decrypt(get_option('wpps_printify_api_key')),
            'chatgpt_key' => $this->encryption->decrypt(get_option('wpps_chatgpt_api_key')),
            'chatgpt_model' => get_option('wpps_chatgpt_model', 'gpt-3.5-turbo'),
            'monthly_cap' => get_option('wpps_chatgpt_monthly_cap', 100),
            'token_limit' => get_option('wpps_chatgpt_token_limit', 1000),
            'temperature' => get_option('wpps_chatgpt_temperature', 0.7),
            'shop_id' => get_option('wpps_printify_shop_id'),
            'shops' => $this->getShops()
        ];

        echo $this->templating->render('admin/settings', $data);
    }

    public function testPrintifyConnection(): void {
        check_ajax_referer('wpps_admin');
        
        try {
            $shops = $this->api->getShops();
            wp_send_json_success([
                'message' => __('Connection successful!', 'wp-woocommerce-printify-sync'),
                'shops' => $shops
            ]);
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    public function testChatGPTConnection(): void {
        check_ajax_referer('wpps_admin');
        
        try {
            $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $_POST['api_key'],
                    'Content-Type' => 'application/json'
                ],
                'body' => json_encode([
                    'model' => 'gpt-3.5-turbo',
                    'messages' => [[
                        'role' => 'user',
                        'content' => 'Test connection'
                    ]]
                ])
            ]);

            if (is_wp_error($response)) {
                throw new \Exception($response->get_error_message());
            }

            $data = json_decode(wp_remote_retrieve_body($response), true);
            
            if (!empty($data['error'])) {
                throw new \Exception($data['error']['message']);
            }

            wp_send_json_success([
                'message' => __('ChatGPT connection successful!', 'wp-woocommerce-printify-sync'),
                'estimated_cost' => $this->estimateMonthlyCost($_POST)
            ]);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    public function saveSettings(): void {
        check_ajax_referer('wpps_admin');
        
        try {
            update_option('wpps_printify_api_key', $this->encryption->encrypt($_POST['printify_key']));
            update_option('wpps_chatgpt_api_key', $this->encryption->encrypt($_POST['chatgpt_key']));
            update_option('wpps_chatgpt_model', sanitize_text_field($_POST['chatgpt_model']));
            update_option('wpps_chatgpt_monthly_cap', absint($_POST['monthly_cap']));
            update_option('wpps_chatgpt_token_limit', absint($_POST['token_limit']));
            update_option('wpps_chatgpt_temperature', floatval($_POST['temperature']));
            update_option('wpps_printify_shop_id', absint($_POST['shop_id']));

            wp_send_json_success([
                'message' => __('Settings saved successfully!', 'wp-woocommerce-printify-sync')
            ]);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    private function estimateMonthlyCost(array $data): float {
        $tokens_per_request = absint($data['token_limit']);
        $requests_per_month = absint($data['monthly_cap']);
        $cost_per_1k_tokens = 0.002; // GPT-3.5-Turbo pricing

        return ($tokens_per_request * $requests_per_month * $cost_per_1k_tokens) / 1000;
    }

    private function getShops(): array {
        try {
            return $this->api->getShops();
        } catch (\Exception $e) {
            return [];
        }
    }
}
