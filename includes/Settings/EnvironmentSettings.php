<?phpnamespace ApolloWeb\WPWooCommercePrintifySync\Settings;class EnvironmentSettings
{
    public static function register()
    {
        add_action('admin_menu', [__CLASS__, 'addSettingsPage']);
        add_action('admin_init', [__CLASS__, 'registerSettings']);
    }    public static function addSettingsPage()
    {
        add_options_page(
            'Environment Settings',
            'Environment',
            'manage_options',
            'environment-settings',
            [__CLASS__, 'renderSettingsPage']
        );
    }    public static function registerSettings()
    {
        register_setting('environment_settings', 'environment_mode');
    }    public static function renderSettingsPage()
    {
        include plugin_dir_path(__FILE__) . '../../templates/admin/environment-settings-page.php';
    }
} Modified by: Rob Owen On: 2025-03-04 06:00:38 Commit Hash 16c804f Modified by: Rob Owen On: 2025-03-04 06:03:34 Commit Hash 16c804f# Commit Hash 16c804f# Initial commit tracked# -------- End Update Summary --------# Commit Hash 16c804f# Initial commit tracked# -------- End Update Summary --------# Commit Hash 16c804f# Initial commit tracked# -------- End Update Summary --------

#
# -------- Update Summary --------
#
# Modified by: Rob Owen
#
# On: 2025-03-04 08:00:31
#
# Change: Added: } Modified by: Rob Owen On: 2025-03-04 06:00:38 Commit Hash 16c804f Modified by: Rob Owen On: 2025-03-04 06:03:34 Commit Hash 16c804f# Commit Hash 16c804f# Initial commit tracked# -------- End Update Summary --------# Commit Hash 16c804f# Initial commit tracked# -------- End Update Summary --------# Commit Hash 16c804f# Initial commit tracked# -------- End Update Summary --------
#
#
# Commit Hash 16c804f
#
