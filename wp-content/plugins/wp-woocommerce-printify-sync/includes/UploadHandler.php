<?php

namespace ApolloWeb\WooCommercePrintifySync;

/**
 * Handle upload size configurations and modifications
 */
class UploadHandler
{
    /**
     * Initialize the upload handler
     */
    public function __construct()
    {
        // Add filters to modify upload sizes
        add_filter('upload_size_limit', array($this, 'filter_upload_size_limit'));
        add_filter('post_max_size', array($this, 'filter_post_max_size'));

        // Add init action to ensure proper PHP configurations
        add_action('admin_init', array($this, 'init_upload_settings'));
    }

    /**
     * Initialize upload settings
     */
    public function init_upload_settings()
    {
        // Convert MB to bytes for ini_set
        $upload_max_size = $this->get_upload_max_size() * 1024 * 1024;

        // Set PHP ini values - Note: This may not work if PHP settings are locked down
        @ini_set('upload_max_filesize', $upload_max_size);
        @ini_set('post_max_size', $upload_max_size);
        @ini_set('memory_limit', $upload_max_size);
    }

    /**
     * Filter WordPress upload size limit
     *
     * @param int $size Current upload size limit
     * @return int Modified upload size limit
     */
    public function filter_upload_size_limit($size)
    {
        $new_size = $this->get_upload_max_size() * 1024 * 1024; // Convert MB to bytes
        return $new_size > 0 ? $new_size : $size;
    }

    /**
     * Filter post max size
     *
     * @param int $size Current post max size
     * @return int Modified post max size
     */
    public function filter_post_max_size($size)
    {
        $new_size = $this->get_upload_max_size() * 1024 * 1024; // Convert MB to bytes
        return $new_size > 0 ? $new_size : $size;
    }

    /**
     * Get the configured upload max size
     *
     * @return int Upload max size in MB
     */
    public function get_upload_max_size()
    {
        return get_option('wps_upload_max_size', 64); // Default to 64MB if not set
    }
}
