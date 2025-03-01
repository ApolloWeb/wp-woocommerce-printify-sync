<?php
namespace ApolloWeb\WPWooCommercePrintifySync;

class Logger {
    public static function log( $message ) {
        if ( WP_DEBUG === true ) {
            error_log( $message );
        }
    }
}