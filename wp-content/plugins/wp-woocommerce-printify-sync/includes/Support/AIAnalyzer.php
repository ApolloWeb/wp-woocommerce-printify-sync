<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Support;

use ApolloWeb\WPWooCommercePrintifySync\Core\Logger;
use ApolloWeb\WPWooCommercePrintifySync\Core\Settings;

/**
 * Uses AI to analyze ticket content and generate responses
 */
class AIAnalyzer {
    /**
     * @var Logger
     */
    private $logger;
    
    /**
     * @var Settings
     */
    private $settings;
    
    /**
     * Constructor
     */
    public function __construct(Logger $logger, Settings $settings) {
        $this->logger = $logger;
        $this->settings = $settings;
    }
    
    /**
     * Analyze email content with GPT-3
     * 
     * @param array $email Email data
     * @return array Analysis results
     */
    public function analyzeEmailContent(array $email): array {
        // Default analysis in case AI fails
        $default_analysis = [
            'category' => 'general',
            'urgency' => 'normal',
            'order_id' => null,
            'customer_id' => null,
            'tone' => 'neutral',
            'key_issues' => []
        ];
        
        // Get API key
        $api_key = $this->settings->get('openai_api_key', '');
        
        if (empty($api_key)) {
            $this->logger->log('OpenAI API key not configured', 'warning');
            
            // Try to extract order ID from the email content
            $order_id = $this->extractOrderId($email['subject'] . ' ' . $email['body']);
            if ($order_id) {
                $default_analysis['order_id'] = $order_id;
            }
            
            // Try to find customer by email
            $customer_id = $this->findCustomerByEmail($email['from_email']);
            if ($customer_id) {
                $default_analysis['customer_id'] = $customer_id;
            }
            
            return $default_analysis;
        }
        
        try {
            // Prepare the content for analysis
            $subject = $email['subject'] ?? '';
            $body = $this->cleanHtmlContent($email['body'] ?? '');
            $content = "Subject: {$subject}\n\nBody: {$body}";
            
            // Prepare the prompt for GPT-3
            $prompt = <<<EOT
As an AI assistant for an e-commerce customer support system, analyze this email content and extract the following information:

1. Category (choose one): general, order, product, shipping, returns, technical
2. Urgency (choose one): low, normal, high, critical
3. Order ID (if mentioned - e.g., #1234, order number 1234)
4. Customer tone: neutral, positive, negative, angry
5. Key issues (max 3 brief points)

Format your response ONLY as valid JSON like:
{
  "category": "category_name",
  "urgency": "urgency_level",
  "order_id": "order_number_or_null",
  "tone": "customer_tone",
  "key_issues": ["issue 1", "issue 2"]
}

Email to analyze:
{$content}
EOT;

            // Call OpenAI API
            $response = $this->callOpenAI($prompt);
            
            // Parse the response
            $parsed_response = $this->parseJsonResponse($response);
            
            if (empty($parsed_response)) {
                throw new \Exception('Failed to parse AI response as JSON');
            }
            
            // Ensure we have all required fields
            $analysis = array_merge($default_analysis, $parsed_response);
            
            // Validate order ID format
            if (!empty($analysis['order_id'])) {
                // Convert formats like "#1234" to just the number
                $analysis['order_id'] = preg_replace('/[^\d]/', '', $analysis['order_id']);
                
                // Verify it's a valid order
                if (!empty($analysis['order_id']) && !wc_get_order($analysis['order_id'])) {
                    $analysis['order_id'] = null;
                }
            }
            
            // Try to find customer by email if not identified by AI
            if (empty($analysis['customer_id'])) {
                $analysis['customer_id'] = $this->findCustomerByEmail($email['from_email']);
            }
            
            $this->logger->log('Successfully analyzed email content with AI', 'debug');
            
            return $analysis;
        } catch (\Exception $e) {
            $this->logger->log('Error analyzing email with AI: ' . $e->getMessage(), 'error');
            
            // Fallback to basic extraction
            $order_id = $this->extractOrderId($email['subject'] . ' ' . $email['body']);
            if ($order_id) {
                $default_analysis['order_id'] = $order_id;
            }
            
            $customer_id = $this->findCustomerByEmail($email['from_email']);
            if ($customer_id) {
                $default_analysis['customer_id'] = $customer_id;
            }
            
            return $default_analysis;
        }
    }
    
    /**
     * Generate a response suggestion for a ticket
     * 
     * @param int $ticket_id Ticket ID
     * @return string Suggested response
     */
    public function generateResponseSuggestion(int $ticket_id): string {
        // Get API key
        $api_key = $this->settings->get('openai_api_key', '');
        
        if (empty($api_key)) {
            return $this->getDefaultResponse($ticket_id);
        }
        
        try {
            // Get ticket details
            $ticket = get_post($ticket_id);
            
            if (!$ticket || $ticket->post_type !== 'support_ticket') {
                throw new \Exception('Ticket not found');
            }
            
            $subject = $ticket->post_title;
            $ticket_content = $this->cleanHtmlContent($ticket->post_content);
            
            // Get customer details
            $customer_name = get_post_meta($ticket_id, '_wpwps_ticket_name', true);
            $customer_email = get_post_meta($ticket_id, '_wpwps_ticket_email', true);
            
            // Get the latest customer comment, if any
            $latest_comment = '';
            $comments = get_comments([
                'post_id' => $ticket_id,
                'type' => 'comment',
                'number' => 1,
                'orderby' => 'comment_date',
                'order' => 'DESC'
            ]);
            
            if (!empty($comments)) {
                $latest_comment = $this->cleanHtmlContent($comments[0]->comment_content);
            }
            
            // Get associated order details
            $order_info = '';
            $order_id = get_post_meta($ticket_id, '_wpwps_order_id', true);
            
            if ($order_id) {
                $order = wc_get_order($order_id);
                
                if ($order) {
                    $order_status = $order->get_status();
                    $order_date = $order->get_date_created()->date('Y-m-d');
                    $order_total = $order->get_formatted_order_total();
                    $order_payment_method = $order->get_payment_method_title();
                    $shipping_method = $order->get_shipping_method();
                    
                    $order_items = [];
                    foreach ($order->get_items() as $item) {
                        $product = $item->get_product();
                        if ($product) {
                            $order_items[] = $item->get_quantity() . 'x ' . $product->get_name();
                        } else {
                            $order_items[] = $item->get_quantity() . 'x ' . $item->get_name();
                        }
                    }
                    
                    $order_info = "Order #{$order_id}\n";
                    $order_info .= "Status: {$order_status}\n";
                    $order_info .= "Date: {$order_date}\n";
                    $order_info .= "Total: {$order_total}\n";
                    $order_info .= "Payment: {$order_payment_method}\n";
                    $order_info .= "Shipping: {$shipping_method}\n";
                    $order_info .= "Items: " . implode(', ', $order_items) . "\n";
                }
            }
            
            // Get company name
            $company_name = $this->settings->get('company_name', get_bloginfo('name'));
            
            // Determine which content to use for generating the response
            $content_for_response = !empty($latest_comment) ? $latest_comment : $ticket_content;
            
            // Prepare the prompt
            $prompt = <<<EOT
As a customer support representative for {$company_name}, your task is to draft a helpful and professional response to the customer's inquiry. The response should be friendly, solution-oriented, and maintain a professional tone.

CUSTOMER INFORMATION:
Name: {$customer_name}
Email: {$customer_email}

TICKET DETAILS:
Subject: {$subject}

{$order_info}

CUSTOMER'S MESSAGE:
{$content_for_response}

Your response should:
1. Start with a personalized greeting using the customer's first name
2. Acknowledge their issue/question with empathy
3. Provide clear and helpful information or solutions
4. Include any relevant links or resources
5. End with a professional closing
6. Do NOT include a signature as it will be added automatically

Write a complete, ready-to-send email response:
EOT;

            // Call OpenAI API
            $response = $this->callOpenAI($prompt, 1000); // Allow a longer response
            
            return $response;
        } catch (\Exception $e) {
            $this->logger->log('Error generating response with AI: ' . $e->getMessage(), 'error');
            return $this->getDefaultResponse($ticket_id);
        }
    }
    
    /**
     * Call OpenAI API
     * 
     * @param string $prompt The prompt to send to the API
     * @param int $max_tokens Maximum number of tokens in the response
     * @return string API response
     */
    private function callOpenAI(string $prompt, int $max_tokens = 800): string {
        $api_key = $this->settings->get('openai_api_key', '');
        $model = $this->settings->get('openai_model', 'gpt-3.5-turbo');
        $api_url = 'https://api.openai.com/v1/chat/completions';
        
        $data = [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a helpful customer support assistant for an e-commerce store.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => $max_tokens,
            'temperature' => 0.7,
        ];
        
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $api_key
        ];
        
        // Initialize cURL
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        // Get API response
        $response = curl_exec($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            throw new \Exception('cURL error: ' . curl_error($ch));
        }
        
        curl_close($ch);
        
        if ($status_code !== 200) {
            throw new \Exception("API error with status code {$status_code}: {$response}");
        }
        
        // Parse the JSON response
        $response_data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Failed to parse API response: ' . json_last_error_msg());
        }
        
        if (!isset($response_data['choices'][0]['message']['content'])) {
            throw new \Exception('Invalid API response format');
        }
        
        return trim($response_data['choices'][0]['message']['content']);
    }
    
    /**
     * Parse JSON from API response
     * 
     * @param string $response Response text
     * @return array|null Parsed JSON as array or null on failure
     */
    private function parseJsonResponse(string $response): ?array {
        // Try to extract JSON from the response
        if (preg_match('/```(?:json)?(.*?)```/s', $response, $matches)) {
            $json_string = trim($matches[1]);
        } else {
            $json_string = trim($response);
        }
        
        // Remove any non-JSON content
        $json_string = preg_replace('/^[^{]*(.*?)[^}]*$/s', '$1', $json_string);
        
        // Parse JSON
        $data = json_decode($json_string, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Try to clean up the JSON string
            $json_string = preg_replace('/[\x00-\x1F\x7F]/u', '', $json_string);
            $data = json_decode($json_string, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return null;
            }
        }
        
        return $data;
    }
    
    /**
     * Clean HTML content for better AI processing
     * 
     * @param string $content HTML content
     * @return string Plain text content
     */
    private function cleanHtmlContent(string $content): string {
        // Remove HTML tags
        $content = strip_tags($content);
        
        // Convert HTML entities
        $content = html_entity_decode($content);
        
        // Remove extra whitespace
        $content = preg_replace('/\s+/', ' ', $content);
        
        // Trim
        $content = trim($content);
        
        return $content;
    }
    
    /**
     * Extract an order ID from text
     * 
     * @param string $text Text to extract from
     * @return int|null Order ID or null if not found
     */
    private function extractOrderId(string $text): ?int {
        // Common patterns for order IDs
        $patterns = [
            '/order\s*#?(\d+)/i',      // "order #1234" or "order 1234"
            '/order\s*number\s*#?(\d+)/i', // "order number #1234"
            '/#(\d+)/',               // "#1234"
            '/\border\b.*?(\d{4,})/',  // Mention of order with 4+ digit number
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $order_id = (int) $matches[1];
                
                // Verify this is a valid order ID
                $order = wc_get_order($order_id);
                
                if ($order) {
                    return $order_id;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Find a customer by email
     * 
     * @param string $email Customer email
     * @return int|null Customer ID or null if not found
     */
    private function findCustomerByEmail(string $email): ?int {
        if (empty($email)) {
            return null;
        }
        
        // Try to find by user account
        $user = get_user_by('email', $email);
        
        if ($user) {
            return $user->ID;
        }
        
        // Try to find by order email
        global $wpdb;
        
        if (class_exists('\\Automattic\\WooCommerce\\Utilities\\OrderUtil') && 
            \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled()) {
            // HPOS is enabled
            $order_id = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}wc_orders WHERE billing_email = %s ORDER BY date_created_gmt DESC LIMIT 1",
                $email
            ));
        } else {
            // Traditional post meta
            $order_id = $wpdb->get_var($wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_billing_email' AND meta_value = %s ORDER BY post_id DESC LIMIT 1",
                $email
            ));
        }
        
        if ($order_id) {
            $order = wc_get_order($order_id);
            if ($order) {
                $customer_id = $order->get_customer_id();
                if ($customer_id > 0) {
                    return $customer_id;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Generate a default response when AI is not available
     * 
     * @param int $ticket_id Ticket ID
     * @return string Default response
     */
    private function getDefaultResponse(int $ticket_id): string {
        // Get customer first name
        $full_name = get_post_meta($ticket_id, '_wpwps_ticket_name', true);
        $first_name = explode(' ', $full_name)[0] ?? 'there';
        
        // Get company name
        $company_name = $this->settings->get('company_name', get_bloginfo('name'));
        
        $response = "Hello {$first_name},

Thank you for contacting {$company_name} customer support. We've received your message and we're reviewing it carefully.

We aim to respond to all inquiries within 24 hours during business days. If your matter is urgent, please feel free to call us directly at " . $this->settings->get('support_phone', '[Your support phone]') . ".

We appreciate your patience and will get back to you as soon as possible.

Best regards,";

        return $response;
    }
}
