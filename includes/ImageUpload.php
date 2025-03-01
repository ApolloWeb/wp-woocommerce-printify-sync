<?php
namespace ApolloWeb\WPWooCommercePrintifySync;

class ImageUpload {
    public function upload_image_to_smush( $image_id ) {
        // Assuming Smush is already installed and activated
        if ( class_exists( 'WP_Smush' ) ) {
            $smush = WP_Smush::get_instance();
            $smush->core()->smushit( $image_id, 'automatic' );
        }
    }
}