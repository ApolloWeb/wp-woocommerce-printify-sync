class Settings {
    // ...existing code...

    public function testGptConnection() {
        check_ajax_referer('wpwps_settings_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error([
                'message' => __('Permission denied.', 'wp-woocommerce-printify-sync')
            ]);
        }
        
        $gpt_api_key = isset($_POST['gpt_api_key']) ? sanitize_text_field($_POST['gpt_api_key']) : '';
        $gpt_tokens = isset($_POST['gpt_tokens']) ? intval($_POST['gpt_tokens']) : 2000;
        $gpt_temperature = isset($_POST['gpt_temperature']) ? floatval($_POST['gpt_temperature']) : 0.7;
        $gpt_budget = isset($_POST['gpt_budget']) ? floatval($_POST['gpt_budget']) : 50;
        
        if (empty($gpt_api_key)) {
            wp_send_json_error([
                'message' => __('OpenAI API key is required.', 'wp-woocommerce-printify-sync')
            ]);
        }

        // Test OpenAI API connection with minimal tokens
        $response = wp_remote_post(
            'https://api.openai.com/v1/chat/completions',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $gpt_api_key,
                    'Content-Type' => 'application/json',
                ],
                'body' => wp_json_encode([
                    'model' => 'gpt-3.5-turbo',
                    'messages' => [[
                        'role' => 'user',
                        'content' => 'Test connection',
                    ]],
                    'max_tokens' => 10,
                    'temperature' => 0.7,
                ]),
            ]
        );

        if (is_wp_error($response)) {
            wp_send_json_error([
                'message' => $response->get_error_message()
            ]);
        }

        $cost_per_token = 0.000002; // GPT-3.5 Turbo rate
        $avg_requests_per_day = 100;
        $daily_cost = $avg_requests_per_day * $gpt_tokens * $cost_per_token;
        $monthly_cost = $daily_cost * 30;

        $response_data = [
            'message' => __('Connection successful!', 'wp-woocommerce-printify-sync'),
            'estimated_cost' => [
                'per_day' => $daily_cost,
                'per_month' => $monthly_cost,
            ],
        ];

        // Add budget warning if estimated cost exceeds budget
        if ($monthly_cost > $gpt_budget) {
            $response_data['budget_warning'] = sprintf(
                __('Estimated monthly cost ($%s) exceeds your budget ($%s).', 'wp-woocommerce-printify-sync'),
                number_format($monthly_cost, 2),
                number_format($gpt_budget, 2)
            );
        }

        wp_send_json_success($response_data);
    }

    // ...existing code...
}
