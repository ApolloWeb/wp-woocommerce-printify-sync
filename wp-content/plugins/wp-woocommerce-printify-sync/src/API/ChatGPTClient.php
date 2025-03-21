<?php
/**
 * ChatGPT API Client.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\API
 */

namespace ApolloWeb\WPWooCommercePrintifySync\API;

use ApolloWeb\WPWooCommercePrintifySync\Services\Logger;
use ApolloWeb\WPWooCommercePrintifySync\Services\EncryptionService;

/**
 * ChatGPT API Client class.
 */
class ChatGPTClient
{
    /**
     * API endpoint.
     *
     * @var string
     */
    private $api_endpoint = 'https://api.openai.com/v1/chat/completions';
    
    /**
     * API key.
     *
     * @var string
     */
    private $api_key;
    
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
     * Temperature setting.
     *
     * @var float
     */
    private $temperature;
    
    /**
     * Monthly token budget.
     *
     * @var int
     */
    private $monthly_budget;
    
    /**
     * Last API response.
     *
     * @var array
     */
    private $last_response;
    
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
        
        // Load API settings
        $this->api_key = $this->encryption->getKey('wpwps_chatgpt_api_key');
        $this->temperature = (float) get_option('wpwps_chatgpt_temperature', 0.7);
        $this->monthly_budget = (int) get_option('wpwps_chatgpt_monthly_budget', 10000);
    }
    
    /**
     * Set API key.
     *
     * @param string $api_key API key.
     * @return void
     */
    public function setApiKey($api_key)
    {
        $this->api_key = $api_key;
    }
    
    /**
     * Set temperature.
     *
     * @param float $temperature Temperature (0-1).
     * @return void
     */
    public function setTemperature($temperature)
    {
        $this->temperature = (float) $temperature;
    }
    
    /**
     * Set monthly budget.
     *
     * @param int $budget Monthly token budget.
     * @return void
     */
    public function setMonthlyBudget($budget)
    {
        $this->monthly_budget = (int) $budget;
    }
    
    /**
     * Get API key.
     *
     * @return string API key.
     */
    public function getApiKey()
    {
        return $this->api_key;
    }
    
    /**
     * Get temperature setting.
     *
     * @return float Temperature.
     */
    public function getTemperature()
    {
        return $this->temperature;
    }
    
    /**
     * Get monthly budget.
     *
     * @return int Monthly token budget.
     */
    public function getMonthlyBudget()
    {
        return $this->monthly_budget;
    }
    
    /**
     * Test the API connection.
     *
     * @return array|WP_Error Test result with token estimate or WP_Error on failure.
     */
    public function testConnection()
    {
        $messages = [
            [
                'role' => 'system',
                'content' => 'You are a helpful assistant that provides concise responses.',
            ],
            [
                'role' => 'user',
                'content' => 'Respond with a short greeting only.',
            ],
        ];
        
        $response = $this->makeRequest($messages);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        // Calculate estimated monthly cost
        $tokens_used = isset($response['usage']['total_tokens']) ? (int) $response['usage']['total_tokens'] : 0;
        $estimated_cost = $this->estimateMonthlyCost($tokens_used);
        
        return [
            'success' => true,
            'message' => isset($response['choices'][0]['message']['content']) ? $response['choices'][0]['message']['content'] : '',
            'tokens_used' => $tokens_used,
            'estimated_cost' => $estimated_cost,
        ];
    }
    
    /**
     * Estimate monthly cost based on a single API call.
     *
     * @param int $tokens_used Tokens used in a single call.
     * @return array Cost estimate.
     */
    public function estimateMonthlyCost($tokens_used)
    {
        // Assuming average email ticket processing per day
        $average_emails_per_day = 10;
        $days_per_month = 30;
        $estimated_monthly_emails = $average_emails_per_day * $days_per_month;
        
        // Multiply by tokens per request
        $estimated_monthly_tokens = $tokens_used * $estimated_monthly_emails;
        
        // Calculate cost (based on $0.002 per 1K tokens for GPT-3.5-turbo)
        $cost_per_thousand_tokens = 0.002;
        $estimated_monthly_cost = ($estimated_monthly_tokens / 1000) * $cost_per_thousand_tokens;
        
        return [
            'emails_per_day' => $average_emails_per_day,
            'emails_per_month' => $estimated_monthly_emails,
            'tokens_per_request' => $tokens_used,
            'estimated_monthly_tokens' => $estimated_monthly_tokens,
            'estimated_monthly_cost' => $estimated_monthly_cost,
        ];
    }
    
    /**
     * Process an email ticket using ChatGPT.
     *
     * @param string $email_content Email content.
     * @return array|WP_Error Processed email data or WP_Error on failure.
     */
    public function processEmailTicket($email_content)
    {
        $messages = [
            [
                'role' => 'system',
                'content' => 'You are a customer service assistant. Extract the following information from customer emails: order number, product mentioned, customer concerns, and whether they\'re requesting a refund or reprint. Classify urgency as high, medium, or low.',
            ],
            [
                'role' => 'user',
                'content' => $email_content,
            ],
        ];
        
        $response = $this->makeRequest($messages);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $content = isset($response['choices'][0]['message']['content']) ? $response['choices'][0]['message']['content'] : '';
        
        // Track token usage
        $tokens_used = isset($response['usage']['total_tokens']) ? (int) $response['usage']['total_tokens'] : 0;
        $this->trackTokenUsage($tokens_used);
        
        // Parse the AI response into structured data
        $parsed_data = $this->parseAIResponse($content);
        
        return [
            'success' => true,
            'original_content' => $email_content,
            'ai_response' => $content,
            'parsed_data' => $parsed_data,
            'tokens_used' => $tokens_used,
        ];
    }
    
    /**
     * Parse AI response into structured data.
     *
     * @param string $ai_response AI response text.
     * @return array Structured data extracted from the response.
     */
    private function parseAIResponse($ai_response)
    {
        $data = [
            'order_number' => '',
            'product' => '',
            'issue_type' => '',
            'urgency' => 'medium',
            'is_refund_request' => false,
            'is_reprint_request' => false,
        ];
        
        // Extract order number - look for pattern like #1234 or Order 1234
        if (preg_match('/(order|#)\s*(\d+)/i', $ai_response, $matches)) {
            $data['order_number'] = $matches[2];
        }
        
        // Extract product name - look for product mentions
        if (preg_match('/product:?\s*([^\.]+)/i', $ai_response, $matches)) {
            $data['product'] = trim($matches[1]);
        }
        
        // Detect if it's a refund request
        if (preg_match('/refund/i', $ai_response)) {
            $data['is_refund_request'] = true;
            $data['issue_type'] = 'refund';
        }
        
        // Detect if it's a reprint request
        if (preg_match('/reprint|replace/i', $ai_response)) {
            $data['is_reprint_request'] = true;
            $data['issue_type'] = 'reprint';
        }
        
        // Extract urgency
        if (preg_match('/urgency:?\s*(high|medium|low)/i', $ai_response, $matches)) {
            $data['urgency'] = strtolower($matches[1]);
        }
        
        return $data;
    }
    
    /**
     * Generate a suggested response for a customer ticket.
     *
     * @param array $ticket_data Ticket data.
     * @return array|WP_Error Suggested response or WP_Error on failure.
     */
    public function generateTicketResponse($ticket_data)
    {
        $prompt = "Customer email: {$ticket_data['content']}\n\n";
        $prompt .= "Order information: Order #{$ticket_data['order_id']}, ";
        $prompt .= "Products: {$ticket_data['products']}\n\n";
        $prompt .= "Issue: {$ticket_data['issue_type']}\n\n";
        
        $messages = [
            [
                'role' => 'system',
                'content' => 'You are a helpful customer service agent. Write a professional, empathetic response to this customer\'s concern. Be concise but thorough, addressing their specific issue.',
            ],
            [
                'role' => 'user',
                'content' => $prompt,
            ],
        ];
        
        $response = $this->makeRequest($messages);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $content = isset($response['choices'][0]['message']['content']) ? $response['choices'][0]['message']['content'] : '';
        
        // Track token usage
        $tokens_used = isset($response['usage']['total_tokens']) ? (int) $response['usage']['total_tokens'] : 0;
        $this->trackTokenUsage($tokens_used);
        
        return [
            'success' => true,
            'suggested_response' => $content,
            'tokens_used' => $tokens_used,
        ];
    }
    
    /**
     * Make a request to the ChatGPT API.
     *
     * @param array $messages Array of message objects.
     * @return array|WP_Error API response or WP_Error on failure.
     */
    public function makeRequest($messages)
    {
        if (empty($this->api_key)) {
            return new \WP_Error('missing_api_key', 'ChatGPT API key is required.');
        }
        
        $args = [
            'method' => 'POST',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
            ],
            'timeout' => 30,
            'body' => json_encode([
                'model' => 'gpt-3.5-turbo',
                'messages' => $messages,
                'temperature' => $this->temperature,
            ]),
        ];
        
        $this->logger->log("Making ChatGPT API request", 'info');
        
        $response = wp_remote_request($this->api_endpoint, $args);
        
        if (is_wp_error($response)) {
            $this->logger->log("ChatGPT API request failed: " . $response->get_error_message(), 'error');
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        $this->logger->log("ChatGPT API response code: {$response_code}", 'info');
        
        $this->last_response = [
            'code' => $response_code,
            'body' => $response_body,
        ];
        
        // Check for errors
        if ($response_code !== 200) {
            $this->logger->log("ChatGPT API error: {$response_code}", 'error');
            $error_data = json_decode($response_body, true);
            $error_message = isset($error_data['error']['message']) ? $error_data['error']['message'] : 'Unknown error';
            return new \WP_Error('api_error', $error_message);
        }
        
        $data = json_decode($response_body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->log("Invalid JSON response: " . json_last_error_msg(), 'error');
            return new \WP_Error('invalid_json', 'Invalid JSON response from API.');
        }
        
        return $data;
    }
    
    /**
     * Track token usage for budget management.
     *
     * @param int $tokens Token count to add to the usage.
     * @return void
     */
    private function trackTokenUsage($tokens)
    {
        $current_month = date('Y-m');
        $usage_key = 'wpwps_chatgpt_usage_' . $current_month;
        
        $current_usage = (int) get_option($usage_key, 0);
        $new_usage = $current_usage + $tokens;
        
        update_option($usage_key, $new_usage);
        
        // Check if we're approaching budget limit
        if ($new_usage > ($this->monthly_budget * 0.8)) {
            $this->logger->log("ChatGPT token usage approaching budget limit: {$new_usage}/{$this->monthly_budget}", 'warning');
            
            // Send admin notification if we're over 80% of budget
            if ($new_usage > ($this->monthly_budget * 0.8) && $current_usage <= ($this->monthly_budget * 0.8)) {
                $this->sendBudgetAlert($new_usage);
            }
        }
    }
    
    /**
     * Get current month's token usage.
     *
     * @return int Current token usage.
     */
    public function getCurrentUsage()
    {
        $current_month = date('Y-m');
        $usage_key = 'wpwps_chatgpt_usage_' . $current_month;
        
        return (int) get_option($usage_key, 0);
    }
    
    /**
     * Send budget alert to admin.
     *
     * @param int $current_usage Current token usage.
     * @return void
     */
    private function sendBudgetAlert($current_usage)
    {
        $admin_email = get_option('admin_email');
        $subject = 'ChatGPT Token Usage Alert - Approaching Budget Limit';
        
        $message = "ChatGPT token usage is approaching the monthly budget limit.\n\n";
        $message .= "Current Usage: {$current_usage} tokens\n";
        $message .= "Monthly Budget: {$this->monthly_budget} tokens\n\n";
        $message .= "This is an automated alert from your WP WooCommerce Printify Sync plugin.";
        
        wp_mail($admin_email, $subject, $message);
    }
    
    /**
     * Get the last API response.
     *
     * @return array Last API response.
     */
    public function getLastResponse()
    {
        return $this->last_response;
    }
}
