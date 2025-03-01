<?php
namespace ApolloWeb\WPWooCommercePrintifySync\Helpers;

class ImagesHelper {
    public function handle_images( $product_id, $images ) {
        $image_ids = [];
        foreach ( $images as $image ) {
            $image_id = $this->upload_image( $image['src'] );
            if ( $image_id ) {
                $image_ids[] = $image_id;
            }
        }

        if ( ! empty( $image_ids ) ) {
            set_post_thumbnail( $product_id, array_shift( $image_ids ) );
            update_post_meta( $product_id, '_product_image_gallery', implode( ',', $image_ids ) );
        }
    }

    private function upload_image( $url ) {
        $upload_dir = wp_upload_dir();
        $image_data = file_get_contents( $url );
        $filename = basename( $url );

        if ( wp_mkdir_p( $upload_dir['path'] ) ) {
            $file = $upload_dir['path'] . '/' . $filename;
        } else {
            $file = $upload_dir['basedir'] . '/' . $filename;
        }

        file_put_contents( $file, $image_data );

        $wp_filetype = wp_check_filetype( $filename, null );
        $attachment = [
            'post_mime_type' => $wp_filetype['type'],
            'post_title'     => sanitize_file_name( $filename ),
            'post_content'   => '',
            'post_status'    => 'inherit',
        ];

        $attach_id = wp_insert_attachment( $attachment, $file );
        require_once( ABSPATH . 'wp-admin/includes/image.php' );
        $attach_data = wp_generate_attachment_metadata( $attach_id, $file );
        wp_update_attachment_metadata( $attach_id, $attach_data );

        return $attach_id;
    }
}