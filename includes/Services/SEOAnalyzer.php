<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class SEOAnalyzer {
    private $ai_analyzer;
    private $settings;

    public function __construct($ai_analyzer, $settings) {
        $this->ai_analyzer = $ai_analyzer;
        $this->settings = $settings;
        
        add_action('wpseo_metabox_entries_general', [$this, 'addAISuggestions']);
        add_action('wp_ajax_wpwps_analyze_seo', [$this, 'analyzeSEO']);
    }

    public function analyzeSEO(): void {
        check_ajax_referer('wpwps_admin');
        
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $content_type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
        
        $prompt = $this->buildSEOPrompt($post_id, $content_type);
        
        try {
            $response = $this->ai_analyzer->analyze($prompt);
            wp_send_json_success([
                'suggestions' => $this->formatSEOSuggestions($response),
                'score' => $this->calculateSEOScore($response)
            ]);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    private function buildSEOPrompt(int $post_id, string $type): string {
        $product = wc_get_product($post_id);
        $title = $product->get_name();
        $description = $product->get_description();
        $meta_title = get_post_meta($post_id, '_yoast_wpseo_title', true);
        $meta_desc = get_post_meta($post_id, '_yoast_wpseo_metadesc', true);
        
        return "As an SEO expert, analyze and optimize this WooCommerce product content for search engines. 
               Product: {$title}
               Description: {$description}
               Current Meta Title: {$meta_title}
               Current Meta Description: {$meta_desc}
               
               Provide specific recommendations for:
               1. Meta title optimization (max 60 chars)
               2. Meta description optimization (max 155 chars)
               3. Focus keyphrase suggestions
               4. Additional keywords for Yoast
               5. Content structure improvements
               
               Format response as JSON with suggested_title, suggested_description, focus_keyphrase, 
               additional_keywords (array), and content_improvements (array).";
    }
}
