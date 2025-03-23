// ...existing code...
        if (strpos($page, 'wpps-') === 0) {
            $page_name = str_replace('wpps-', '', $page);
            wp_enqueue_style(
                "wpps-{$page_name}",
                WPPS_PUBLIC_URL . "css/wpwps-{$page_name}.css",
                [],
                WPPS_VERSION
            );
            
            wp_enqueue_script(
                "wpps-{$page_name}",
                WPPS_PUBLIC_URL . "js/wpwps-{$page_name}.js",
                ['jquery'],
                WPPS_VERSION,
                true
            );
        }
// ...existing code...
