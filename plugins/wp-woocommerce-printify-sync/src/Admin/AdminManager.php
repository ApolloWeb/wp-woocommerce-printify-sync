<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

class AdminManager {
    private AssetManager $assetManager;
    private MenuManager $menuManager;
    
    public function register(): void {
        add_action('admin_menu', [$this->menuManager, 'registerMenus']);
        add_action('admin_enqueue_scripts', [$this->assetManager, 'enqueueAssets']);
        add_action('wp_ajax_wpwps_test_api', [$this, 'handleApiTest']);
    }
    
    public function handleApiTest(): void {
        try {
            check_ajax_referer('wpwps_api_test');
            
            $result = $this->testApiConnection();
            wp_send_json_success($result);
            
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }
}