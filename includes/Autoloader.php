<?php

spl_autoload_register( function ( $class ) {
    $prefix = 'ApolloWeb\\WPWoocomercePrintifySync\\';
    $base_dir = WWPS_PLUGIN_DIR . 'includes/';

    $len = strlen( $prefix );
    if ( strncmp( $prefix, $class, $len ) !== 0 ) {
        return;
    }

    $relative_class = substr( $class, $len );
    $file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

    if ( file_exists( $file ) ) {
        require $file;
    }
} );