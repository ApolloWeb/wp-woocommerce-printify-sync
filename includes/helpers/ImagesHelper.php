<?php

namespace ApolloWeb\WPWoocomercePrintifySync\Helpers;

class ImagesHelper {
    public static function handle_images($product_id, $images) {
        $existing_images = get_post_meta($product_id, '_product_image_gallery', true);
        $existing_images = explode(',', $existing_images);

        $new_images = [];
        foreach ($images as $image) {
            $image_id = self::upload_image($image['src']);
            $new_images[] = $image_id;
            if ($image['is_primary']) {
                set_post_thumbnail($product_id, $image_id);
            }
        }

        // Update only if images have changed.
        if (array_diff($existing_images, $new_images) || array_diff($new_images, $existing_images)) {
            update_post_meta($product_id, '_product_image_gallery', implode(',', $new_images));
        }
    }

    private static function upload_image($image_url) {
        $upload_dir = wp_upload_dir();
        $image_data = file_get_contents($image_url);
        $filename = basename($image_url);
        if(wp_mkdir_p($upload_dir['path'])) {
            $file = $upload_dir['path'] . '/' . $filename;
        } else {
            $file = $upload_dir['basedir'] . '/' . $filename;
        }
        file_put_contents($file, $image_data);

        $wp_filetype = wp_check_filetype($filename, null);
        $attachment = array(
            'post_mime_type' => $wp_filetype['type'],
            'post_title' => sanitize_file_name($filename),
            'post_content' => '',
            'post_status' => 'inherit'
        );
        $attach_id = wp_insert_attachment($attachment, $file);
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $file);
        wp_update_attachment_metadata($attach_id, $attach_data);

        return $attach_id;
    }
}