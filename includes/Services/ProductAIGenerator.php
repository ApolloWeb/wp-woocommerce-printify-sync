<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class ProductAIGenerator {
    private $ai_analyzer;
    private $settings;
    
    public function __construct($ai_analyzer, $settings) {
        $this->ai_analyzer = $ai_analyzer;
        $this->settings = $settings;
        
        add_action('add_meta_boxes', [$this, 'addAIMetaBox']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('wp_ajax_wpwps_generate_product_content', [$this, 'generateContent']);
    }

    public function generateContent(): void {
        check_ajax_referer('wpwps_admin');
        
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $content_type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
        $product_data = $this->getProductData($product_id);

        $prompt = $this->buildPrompt($content_type, $product_data);
        
        try {
            $response = $this->ai_analyzer->analyze($prompt);
            wp_send_json_success($this->formatAIResponse($response, $content_type));
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    private function buildPrompt(string $type, array $data): string {
        $base_prompt = "You are an expert e-commerce copywriter specializing in POD (Print on Demand) products. ";
        
        switch ($type) {
            case 'title':
                return $base_prompt . "Create a compelling, SEO-optimized product title for a {$data['type']} with the design '{$data['design']}'. " .
                       "Must be 140 characters or less. Target both WooCommerce and Etsy audiences. " .
                       "Include key product features and design elements.";
            
            case 'description':
                return $base_prompt . "Write an engaging, SEO-friendly product description for a {$data['type']} featuring '{$data['design']}'. " .
                       "Include key features: {$data['features']}. Highlight quality, design, and target audience. " .
                       "Format with bullet points for key features. Optimize for both WooCommerce and Etsy.";
            
            case 'tags':
                return $base_prompt . "Generate up to 13 SEO-optimized tags for a {$data['type']} with design '{$data['design']}'. " .
                       "Each tag must be 20 characters or less. Include product type, style, design elements, and target audience. " .
                       "Format as comma-separated values. Optimize for both WooCommerce and Etsy search.";
            
            default:
                return '';
        }
    }

    private function formatAIResponse(array $response, string $type): array {
        switch ($type) {
            case 'tags':
                $tags = explode(',', $response['content']);
                $tags = array_map('trim', $tags);
                $tags = array_filter($tags, fn($tag) => strlen($tag) <= 20);
                $tags = array_slice($tags, 0, 13);
                return ['tags' => $tags];
            
            case 'title':
                $title = substr($response['content'], 0, 140);
                return ['title' => $title];
            
            default:
                return $response;
        }
    }

    private function getProductData(int $product_id): array {
        $product = wc_get_product($product_id);
        return [
            'type' => $product->get_type(),
            'design' => get_post_meta($product_id, '_wpwps_design_name', true),
            'features' => get_post_meta($product_id, '_wpwps_features', true)
        ];
    }
}
