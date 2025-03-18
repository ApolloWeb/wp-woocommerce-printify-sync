<?php

namespace ApolloWeb\WPWooCommercePrintifySync;

// Login: ApolloWeb
// Timestamp: 2025-03-18 07:24:52

class ImageHandler implements ServiceProvider
{
    public function boot()
    {
        // No actions needed on boot
    }

    /**
     * Register services to the container
     * 
     * @return void
     */
    public function register()
    {
        // Register image handling services
        add_action('wp_wc_printify_process_images', [$this, 'processProductImages']);
    }

    public function addImageToMediaLibrary($image_url)
    {
        $upload_dir = wp_upload_dir();
        $image_data = file_get_contents($image_url);
        $filename = basename($image_url);

        if (wp_mkdir_p($upload_dir['path'])) {
            $file = $upload_dir['path'] . '/' . $filename;
        } else {
            $file = $upload_dir['basedir'] . '/' . $filename;
        }

        file_put_contents($file, $image_data);

        $wp_filetype = wp_check_filetype($filename, null);
        $attachment = [
            'post_mime_type' => $wp_filetype['type'],
            'post_title' => sanitize_file_name($filename),
            'post_content' => '',
            'post_status' => 'inherit'
        ];

        $attach_id = wp_insert_attachment($attachment, $file);
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $file);
        wp_update_attachment_metadata($attach_id, $attach_data);

        return $attach_id;
    }

    public function setProductImage($product_id, $image_url)
    {
        $attach_id = $this->addImageToMediaLibrary($image_url);
        set_post_thumbnail($product_id, $attach_id);
    }
}