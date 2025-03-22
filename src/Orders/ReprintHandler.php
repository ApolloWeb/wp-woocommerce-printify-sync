<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Orders;

class ReprintHandler {
    private $api_client;
    private $logger;
    private $analyzer;

    public function init() {
        add_action('wp_ajax_wpwps_request_reprint', [$this, 'handleReprintRequest']);
        add_action('wp_ajax_wpwps_submit_reprint_issue', [$this, 'submitReprintIssue']);
        add_action('woocommerce_order_status_changed', [$this, 'handleStatusChange'], 10, 4);
    }

    public function handleReprintRequest() {
        check_ajax_referer('wpwps_ajax_nonce', 'nonce');

        $order_id = isset($_POST['order_id']) ? absint($_POST['order_id']) : 0;
        $reason = sanitize_text_field($_POST['reason'] ?? '');
        $images = $_FILES['images'] ?? [];

        try {
            // Analyze issue using AI
            $analysis = $this->analyzer->analyzeIssue(
                $order_id, 
                $reason, 
                $images
            );

            // Add AI recommendations to order notes
            $order = wc_get_order($order_id);
            $order->add_order_note(sprintf(
                'AI Analysis: %s - Recommended Action: %s',
                $analysis['issue_type'],
                $analysis['recommended_action']
            ));

            if ($analysis['recommended_action'] === 'reprint') {
                $result = $this->submitReprintToPrintify($order_id, $reason, $images, $analysis);
            } else {
                throw new \Exception('AI suggests refund instead of reprint');
            }

            wp_send_json_success([
                'result' => $result,
                'analysis' => $analysis
            ]);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    private function submitReprintToPrintify($order_id, $reason, $images, $analysis) {
        $printify_order_id = get_post_meta($order_id, '_printify_order_id', true);
        
        return $this->api_client->request(
            "orders/{$printify_order_id}/reprint",
            'POST',
            [
                'reason' => $reason,
                'images' => $this->processReprintImages($images),
                'analysis' => $analysis,
                'severity' => $analysis['severity'],
                'issue_type' => $analysis['issue_type']
            ]
        );
    }
}
