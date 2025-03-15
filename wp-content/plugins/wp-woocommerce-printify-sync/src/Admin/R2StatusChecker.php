<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

class R2StatusChecker
{
    private string $currentTime = '2025-03-15 19:11:19';
    private string $currentUser = 'ApolloWeb';

    public function __construct()
    {
        add_action('wp_ajax_wpwps_check_r2_status', [$this, 'checkR2Status']);
    }

    public function checkR2Status(): void
    {
        try {
            $r2Service = new \ApolloWeb\WPWooCommercePrintifySync\Services\R2StorageService();
            
            // Test upload
            $testFile = $this->createTestFile();
            $r2Url = $r2Service->uploadImage($testFile, 'test.txt');
            
            // Test delete
            $r2Service->deleteImage(basename($r2Url));
            
            @unlink($testFile);

            wp_send_json_success([
                'message' => __('R2 connection successful', 'wp-woocommerce-printify-sync')
            ]);

        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    private function createTestFile(): string
    {
        $file = wp_tempnam('r2-test');
        file_put_contents($file, 'R2 test file - ' . $this->currentTime);
        return $file;
    }
}