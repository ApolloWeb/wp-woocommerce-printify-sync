<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class ImageHandler {
    private $logger;
    private $action_scheduler;

    const IMAGE_IMPORT_ACTION = 'wpwps_import_image';

    public function __construct(Logger $logger, ActionSchedulerService $action_scheduler) {
        $this->logger = $logger;
        $this->action_scheduler = $action_scheduler;
    }

    public function init() {
        add_action(self::IMAGE_IMPORT_ACTION, [$this, 'importImage']);
        add_filter('wp_smush_image', [$this, 'markAsProcessed'], 10, 2);
    }

    public function scheduleImageImport($image_url, $product_id, $is_featured = false) {
        return $this->action_scheduler->schedule(
            self::IMAGE_IMPORT_ACTION,
            [
                'url' => $image_url,
                'product_id' => $product_id,
                'is_featured' => $is_featured
            ],
            ['group' => 'image-import']
        );
    }

    public function importImage($args) {
        $url = $args['url'] ?? '';
        $product_id = $args['product_id'] ?? 0;
        $is_featured = $args['is_featured'] ?? false;

        if (!$url || !$product_id) {
            $this->logger->error('Invalid image import data');
            return;
        }

        try {
            // Download image to media library
            $attachment_id = media_sideload_image($url, $product_id, '', 'id');

            if (is_wp_error($attachment_id)) {
                throw new \Exception($attachment_id->get_error_message());
            }

            // Set as featured if needed
            if ($is_featured) {
                set_post_thumbnail($product_id, $attachment_id);
            }

            // Add product attachment
            else {
                $this->attachToProduct($attachment_id, $product_id);
            }

            $this->logger->info(sprintf('Image imported successfully: %s', $url));

        } catch (\Exception $e) {
            $this->logger->error(sprintf('Image import failed: %s', $e->getMessage()));
        }
    }

    private function attachToProduct($attachment_id, $product_id) {
        $current = get_post_meta($product_id, '_product_image_gallery', true);
        $gallery = $current ? explode(',', $current) : [];
        $gallery[] = $attachment_id;
        
        update_post_meta($product_id, '_product_image_gallery', implode(',', array_unique($gallery)));
    }

    public function markAsProcessed($image_id, $stats) {
        update_post_meta($image_id, '_wpwps_processed', true);
        return $image_id;
    }
}
