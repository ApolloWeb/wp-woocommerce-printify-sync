<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

class MediaStatusChecker
{
    private string $currentTime = '2025-03-15 19:13:19';
    private string $currentUser = 'ApolloWeb';

    public function checkMediaStatus(): array
    {
        global $wpdb;

        $status = [
            'total_images' => 0,
            'r2_offloaded' => 0,
            'pending_offload' => 0,
            'failed_offload' => 0
        ];

        $images = $wpdb->get_results("
            SELECT p.ID, pm.meta_value as is_r2
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_wpwps_is_r2'
            WHERE p.post_type = 'attachment'
            AND p.post_mime_type LIKE 'image/%'
        ");

        foreach ($images as $image) {
            $status['total_images']++;
            
            if ($image->is_r2) {
                $status['r2_offloaded']++;
            } else {
                $status['pending_offload']++;
            }
        }

        $status['failed_offload'] = $wpdb->get_var("
            SELECT COUNT(*)
            FROM {$wpdb->postmeta}
            WHERE meta_key = '_wpwps_r2_error'
        ");

        return $status;
    }
}