<?php
/**
 * Image Helper class
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Helpers
 * @version 1.0.0
 * @author ApolloWeb
 * @since 1.0.0
 */
 
namespace ApolloWeb\WPWooCommercePrintifySync\Helpers;

class ImageHelper {
    private static $instance = null;
    private $timestamp = '2025-03-05 18:39:40';
    private $user = 'ApolloWeb';
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Schedule image import for a product
     */
    public function scheduleImageImport($product_id, $printify_product) {
        $images = [];
        
        // Collect main product images
        if (!empty($printify_product['images'])) {
            foreach ($printify_product['images'] as $image) {
                if (!empty($image['src'])) $images[] = $image['src'];
            }
        }
        
        // Add variant images if they exist
        if (!empty($printify_product['variants'])) {
            foreach ($printify_product['variants'] as $variant) {
                if (!empty($variant['image_url']) && !in_array($variant['image_url'], $images)) {
                    $images[] = $variant['image_url'];
                }
            }
        }
        
        if (empty($images)) return false;
        
        // Store image URLs for later import
        update_post_meta($product_id, '_printify_images_to_import', $images);
        
        // Schedule async import
        wp_schedule_single_event(time() + 15, 'wpwprintifysync_import_product_images', [
            'product_id' => $product_id,
            'user' => $this->user,
            'timestamp' => $this->timestamp
        ]);
        
        return count($images);
    }
    
    /**
     * Import product images
     */
    public function importProductImages($product_id, $user, $timestamp) {
        $images = get_post_meta($product_id, '_printify_images_to_import', true);
        if (empty($images) || !is_array($images)) return;
        
        $product = wc_get_product($product_id);
        if (!$product) return;
        
        $gallery_ids = [];
        
        foreach ($images as $index => $url) {
            $attachment_id = $this->importImageFromUrl($url, $product_id);
            
            if ($attachment_id) {
                if ($index === 0) {
                    $product->set_image_id($attachment_id);
                } else {
                    $gallery_ids[] = $attachment_id;
                }
            }
        }
        
        if (!empty($gallery_ids)) {
            $product->set_gallery_image_ids($gallery_ids);
        }
        
        $product->save();
        delete_post_meta($product_id, '_printify_images_to_import');
    }
    
    /**
     * Import image from URL
     */
    public function importImageFromUrl($url, $product_id) {
        // Check for existing image
        $attachment_id = $this->getAttachmentIdFromUrl($url);
        if ($attachment_id) return $attachment_id;
        
        // Download image
        $upload = $this->downloadRemoteImage($url);
        if (is_wp_error($upload) || empty($upload['file'])) return false;
        
        $file_path = $upload['file'];
        $file_type = wp_check_filetype(basename($file_path), null);
        
        // Create attachment
        $attachment = [
            'post_mime_type' => $file_type['type'],
            'post_title' => sanitize_file_name(basename($file_path)),
            'post_content' => '',
            'post_status' => 'inherit',
            'post_parent' => $product_id
        ];
        
        $attachment_id = wp_insert_attachment($attachment, $file_path, $product_id);
        
        if (is_wp_error($attachment_id)) return false;
        
        // Generate metadata
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attachment_data = wp_generate_attachment_metadata($attachment_id, $file_path);
        wp_update_attachment_metadata($attachment_id, $attachment_data);
        
        // Store source URL
        update_post_meta($attachment_id, '_printify_source_url', $url);
        update_post_meta($attachment_id, '_printify_imported_at', $this->timestamp);
        update_post_meta($attachment_id, '_printify_imported_by', $this->user);
        
        return $attachment_id;
    }
    
    /**
     * Get attachment ID from URL
     */
    public function getAttachmentIdFromUrl($url) {
        global $wpdb;
        $attachment_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_printify_source_url' AND meta_value = %s LIMIT 1",
            $url
        ));
        
        return $attachment_id ? (int)$attachment_id : false;
    }
    
    /**
     * Download remote image
     */
    public function downloadRemoteImage($url) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        
        // Generate a unique filename
        $upload_dir = wp_upload_dir();
        $filename = wp_unique_filename($upload_dir['path'], basename(parse_url($url, PHP_URL_PATH)));
        $filepath = $upload_dir['path'] . '/' . $filename;
        
        // Download file
        $response = wp_remote_get($url, ['timeout' => 30, 'sslverify' => false]);
        
        if (is_wp_error($response) || empty(wp_remote_retrieve_body($response))) {
            return new \WP_Error('download_failed', __('Failed to download image', 'wp-woocommerce-printify-sync'));
        }
        
        $result = file_put_contents($filepath, wp_remote_retrieve_body($response));
        
        if (!$result) {
            return new \WP_Error('save_failed', __('Failed to save image', 'wp-woocommerce-printify-sync'));
        }
        
        return [
            'file' => $filepath,
            'url' => $upload_dir['url'] . '/' . $filename
        ];
    }
}