<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Products\Helpers;

use ApolloWeb\WPWooCommercePrintifySync\Logger\LoggerInterface;

/**
 * Helper class for handling product images with SMUSH Pro integration
 */
class ImageHandler {
    /**
     * @var LoggerInterface
     */
    private $logger;
    
    /**
     * @var array Image handling options
     */
    private $options;
    
    /**
     * Constructor
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
        $this->options = get_option('wpwps_image_handling_options', [
            'use_smush_pro' => true,
            'first_as_featured' => true,
            'optimize_images' => true,
            'use_cdn' => true
        ]);
    }
    
    /**
     * Process product images
     *
     * @param int $product_id WooCommerce product ID
     * @param array $images Array of image URLs
     * @return array Array of processed image IDs
     */
    public function process_product_images($product_id, $images) {
        $image_ids = [];
        
        if (empty($images)) {
            return $image_ids;
        }
        
        try {
            // Make sure we have required WP functions
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            
            // First image is always the featured image
            if (!empty($images[0]['src'])) {
                $featured_id = $this->import_image($product_id, $images[0]['src']);
                
                if ($featured_id) {
                    $image_ids[] = $featured_id;
                    set_post_thumbnail($product_id, $featured_id);
                    
                    $this->logger->log_info(
                        'images', 
                        sprintf('Set featured image %d for product %d', $featured_id, $product_id)
                    );
                }
            }
            
            // All other images go to gallery
            $gallery_ids = [];
            if (count($images) > 1) {
                // Skip the first image (featured image)
                for ($i = 1; $i < count($images); $i++) {
                    if (!empty($images[$i]['src'])) {
                        $image_id = $this->import_image($product_id, $images[$i]['src']);
                        if ($image_id) {
                            $image_ids[] = $image_id;
                            $gallery_ids[] = $image_id;
                        }
                    }
                }
                
                if (!empty($gallery_ids)) {
                    update_post_meta($product_id, '_product_image_gallery', implode(',', $gallery_ids));
                    
                    $this->logger->log_info(
                        'images', 
                        sprintf('Added %d gallery images for product %d', count($gallery_ids), $product_id)
                    );
                }
            }
            
        } catch (\Exception $e) {
            $this->logger->log_error(
                'images', 
                sprintf('Error processing images: %s', $e->getMessage())
            );
        }
        
        return $image_ids;
    }
    
    /**
     * Import a single image and apply SMUSH Pro optimization
     *
     * @param int $product_id WooCommerce product ID
     * @param string $image_url Image URL
     * @return int|false Attachment ID or false on failure
     */
    public function import_image($product_id, $image_url) {
        if (empty($image_url)) {
            return false;
        }
        
        try {
            // Check if image exists by URL hash
            $existing_id = $this->get_image_by_url_hash($image_url);
            if ($existing_id) {
                $this->logger->log_info(
                    'images', 
                    sprintf('Using existing image %d for URL %s', $existing_id, $image_url)
                );
                return $existing_id;
            }
            
            // Download image
            $temp_file = download_url($image_url);
            
            if (is_wp_error($temp_file)) {
                $this->logger->log_error(
                    'images', 
                    sprintf('Error downloading image: %s', $temp_file->get_error_message())
                );
                return false;
            }
            
            // Prepare file array
            $file_array = [
                'name' => basename($image_url),
                'tmp_name' => $temp_file
            ];
            
            // Handle sideload
            $attachment_id = media_handle_sideload($file_array, $product_id);
            
            if (is_wp_error($attachment_id)) {
                @unlink($temp_file);
                $this->logger->log_error(
                    'images', 
                    sprintf('Error handling image sideload: %s', $attachment_id->get_error_message())
                );
                return false;
            }
            
            // Store URL hash for future reference
            update_post_meta($attachment_id, '_wpwps_source_url_hash', md5($image_url));
            update_post_meta($attachment_id, '_wpwps_source_url', esc_url_raw($image_url));
            update_post_meta($attachment_id, '_wpwps_product_id', $product_id);
            
            // Apply SMUSH Pro optimization if available and enabled
            if ($this->options['use_smush_pro'] && $this->options['optimize_images']) {
                $this->apply_smush_optimization($attachment_id);
            }
            
            $this->logger->log_success(
                'images', 
                sprintf('Successfully imported image %d from %s', $attachment_id, $image_url)
            );
            
            return $attachment_id;
        } catch (\Exception $e) {
            $this->logger->log_error(
                'images', 
                sprintf('Error importing image: %s', $e->getMessage())
            );
            return false;
        }
    }
    
    /**
     * Get existing image by URL hash
     *
     * @param string $image_url Image URL
     * @return int|false Attachment ID or false if not found
     */
    private function get_image_by_url_hash($image_url) {
        global $wpdb;
        
        $url_hash = md5($image_url);
        
        $query = $wpdb->prepare(
            "SELECT post_id FROM $wpdb->postmeta 
             WHERE meta_key = '_wpwps_source_url_hash' 
             AND meta_value = %s
             LIMIT 1",
            $url_hash
        );
        
        $attachment_id = $wpdb->get_var($query);
        
        if ($attachment_id && get_post_type($attachment_id) === 'attachment') {
            return (int)$attachment_id;
        }
        
        return false;
    }
    
    /**
     * Apply SMUSH Pro optimization to an image
     *
     * @param int $attachment_id Attachment ID
     * @return bool Whether Smush was applied
     */
    private function apply_smush_optimization($attachment_id) {
        // Check if SMUSH Pro is active - both basic function and CDN function
        if (function_exists('wp_smush_resize_from_meta_data')) {
            try {
                $metadata = wp_get_attachment_metadata($attachment_id);
                
                if ($metadata) {
                    // Apply SMUSH basic optimization
                    if (function_exists('wp_smush_resize_from_meta_data')) {
                        wp_smush_resize_from_meta_data($metadata, $attachment_id);
                        $this->logger->log_info(
                            'images', 
                            sprintf('Applied SMUSH optimization to image %d', $attachment_id)
                        );
                    }
                    
                    // Apply CDN if enabled and SMUSH CDN is available
                    if ($this->options['use_cdn'] && function_exists('wp_smush_get_cdn_class') && method_exists(wp_smush_get_cdn_class(), 'update_image')) {
                        $cdn_class = wp_smush_get_cdn_class();
                        if (method_exists($cdn_class, 'update_image')) {
                            $cdn_class->update_image($attachment_id);
                            $this->logger->log_info(
                                'images', 
                                sprintf('Offloaded image %d to SMUSH CDN', $attachment_id)
                            );
                        }
                    }
                    
                    return true;
                }
            } catch (\Exception $e) {
                $this->logger->log_warning(
                    'images', 
                    sprintf('Error applying SMUSH to image %d: %s', $attachment_id, $e->getMessage())
                );
            }
        }
        
        return false;
    }
    
    /**
     * Import variant image
     *
     * @param int $product_id WooCommerce product ID
     * @param array $variant Printify variant
     * @return int|false Attachment ID or false on failure
     */
    public function import_variant_image($product_id, $variant) {
        if (empty($variant['image_url']) || empty($variant['id'])) {
            return false;
        }
        
        try {
            $image_id = $this->import_image($product_id, $variant['image_url']);
            
            if (!$image_id) {
                return false;
            }
            
            // Find the variation ID by Printify variant ID
            $variation_id = $this->get_variation_by_printify_variant_id($product_id, $variant['id']);
            
            if ($variation_id) {
                // Set the image for this variation
                update_post_meta($variation_id, '_thumbnail_id', $image_id);
                
                $this->logger->log_info(
                    'images', 
                    sprintf('Set image %d for variation %d', $image_id, $variation_id)
                );
                
                return $image_id;
            } else {
                $this->logger->log_warning(
                    'images', 
                    sprintf('Could not find variation for variant ID %s', $variant['id'])
                );
            }
            
            return $image_id;
        } catch (\Exception $e) {
            $this->logger->log_error(
                'images', 
                sprintf('Error importing variant image: %s', $e->getMessage())
            );
            return false;
        }
    }
    
    /**
     * Get variation ID by Printify variant ID
     *
     * @param int $product_id WooCommerce product ID
     * @param string $variant_id Printify variant ID
     * @return int|false Variation ID or false if not found
     */
    private function get_variation_by_printify_variant_id($product_id, $variant_id) {
        global $wpdb;
        
        $query = $wpdb->prepare(
            "SELECT post_id FROM $wpdb->postmeta 
             WHERE meta_key = %s 
             AND meta_value = %s
             AND post_id IN (
                 SELECT ID FROM $wpdb->posts 
                 WHERE post_parent = %d 
                 AND post_type = 'product_variation'
             )",
            '_printify_variant_id',
            $variant_id,
            $product_id
        );
        
        $variation_id = $wpdb->get_var($query);
        
        return $variation_id ? (int)$variation_id : false;
    }
}
