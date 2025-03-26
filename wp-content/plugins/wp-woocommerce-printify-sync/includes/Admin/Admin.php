class Admin {
    public function init() {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    public function enqueue_scripts($hook) {
        // Only load on plugin pages
        if (strpos($hook, 'wpwps') === false) {
            return;
        }

        // Enqueue Bootstrap
        wp_enqueue_style('wpwps-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css');
        wp_enqueue_script('wpwps-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js', [], '5.1.3', true);

        // Enqueue Font Awesome
        wp_enqueue_style('wpwps-fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css');

        // Plugin styles
        wp_enqueue_style('wpwps-admin', plugins_url('assets/css/wpwps-admin.css', WPWPS_PLUGIN_FILE));
        wp_enqueue_style('wpwps-settings', plugins_url('assets/css/wpwps-settings.css', WPWPS_PLUGIN_FILE));

        // Plugin scripts
        wp_enqueue_script('wpwps-admin', plugins_url('assets/js/wpwps-admin.js', WPWPS_PLUGIN_FILE), ['jquery'], WPWPS_VERSION, true);
        wp_enqueue_script('wpwps-settings', plugins_url('assets/js/wpwps-settings.js', WPWPS_PLUGIN_FILE), ['jquery'], WPWPS_VERSION, true);
        wp_enqueue_script('wpwps-logs', plugins_url('assets/js/wpwps-logs.js', WPWPS_PLUGIN_FILE), ['jquery'], WPWPS_VERSION, true);

        // Add media uploader support
        wp_enqueue_media();

        // Localize script
        wp_localize_script('wpwps-admin', 'wpwps', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpwps-nonce')
        ]);
    }

    public function render_admin_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Get current tab
        $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'dashboard';

        // Enqueue required scripts and styles
        wp_enqueue_script('wpwps-admin');
        wp_enqueue_style('wpwps-admin');

        // Load appropriate template
        switch ($current_tab) {
            case 'settings':
                include WPWPS_PLUGIN_DIR . 'templates/wpwps-settings.blade.php';
                break;
            case 'logs':
                include WPWPS_PLUGIN_DIR . 'templates/wpwps-logs.blade.php';
                break;
            default:
                include WPWPS_PLUGIN_DIR . 'templates/wpwps-dashboard.blade.php';
                break;
        }
    }
}