<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

class Settings
{
    private string $currentTime;
    private string $currentUser;

    public function __construct()
    {
        $this->currentTime = '2025-03-15 18:22:27';
        $this->currentUser = 'ApolloWeb';
        
        add_action('admin_menu', [$this, 'addSettingsPage']);
        add_action('admin_init', [$this, 'registerSettings']);
    }

    public function addSettingsPage(): void
    {
        add_submenu_page(
            'wpwps',
            'API Settings',
            'API Settings',
            'manage_options',
            'wpwps-settings',
            [$this, 'renderSettingsPage']
        );
    }

    public function registerSettings(): void
    {
        register_setting('wpwps_settings', 'wpwps_api_key');
        register_setting('wpwps_settings', 'wpwps_api_base_url');
        register_setting('wpwps_settings', 'wpwps_api_endpoints');
    }

    public function renderSettingsPage(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (isset($_POST['wpwps_add_endpoint'])) {
            $this->handleAddEndpoint();
        }

        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="wpwps-settings-timestamp">
                Last Updated: <?php echo esc_html($this->currentTime); ?>
                by <?php echo esc_html($this->currentUser); ?>
            </div>

            <form method="post" action="options.php">
                <?php
                settings_fields('wpwps_settings');
                do_settings_sections('wpwps_settings');
                ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">API Key</th>
                        <td>
                            <input type="password" 
                                   name="wpwps_api_key" 
                                   value="<?php echo esc_attr(get_option('wpwps_api_key')); ?>" 
                                   class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">API Base URL</th>
                        <td>
                            <input type="url" 
                                   name="wpwps_api_base_url" 
                                   value="<?php echo esc_url(get_option('wpwps_api_base_url', 'https://api.printify.com/v1')); ?>" 
                                   class="regular-text">
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>

            <h2>API Endpoints</h2>
            <div class="wpwps-endpoints-table">
                <?php $this->renderEndpointsTable(); ?>
            </div>

            <h3>Add New Endpoint</h3>
            <form method="post" action="">
                <?php wp_nonce_field('wpwps_add_endpoint', 'wpwps_endpoint_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Endpoint Name</th>
                        <td>
                            <input type="text" name="endpoint_name" required class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Path</th>
                        <td>
                            <input type="text" name="endpoint_path" required class="regular-text">
                            <p class="description">Use {param} for path parameters. Example: /shops/{shop_id}/products</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Method</th>
                        <td>
                            <select name="endpoint_method">
                                <option value="GET">GET</option>
                                <option value="POST">POST</option>
                                <option value="PUT">PUT</option>
                                <option value="DELETE">DELETE</option>
                            </select>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Add Endpoint', 'secondary', 'wpwps_add_endpoint'); ?>
            </form>
        </div>
        <?php
    }

    private function renderEndpointsTable(): void
    {
        $endpoints = get_option('wpwps_api_endpoints', []);
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Path</th>
                    <th>Method</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($endpoints as $name => $endpoint): ?>
                <tr>
                    <td><?php echo esc_html($name); ?></td>
                    <td><?php echo esc_html($endpoint['path']); ?></td>
                    <td><?php echo esc_html($endpoint['method']); ?></td>
                    <td>
                        <button class="button button-small delete-endpoint" 
                                data-endpoint="<?php echo esc_attr($name); ?>">
                            Delete
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    private function handleAddEndpoint(): void
    {
        if (!isset($_POST['wpwps_endpoint_nonce']) || 
            !wp_verify_nonce($_POST['wpwps_endpoint_nonce'], 'wpwps_add_endpoint')) {
            wp_die('Invalid nonce');
        }

        $endpoints = get_option('wpwps_api_endpoints', []);
        $name = sanitize_text_field($_POST['endpoint_name']);
        
        $endpoints[$name] = [
            'path' => sanitize_text_field($_POST['endpoint_path']),
            'method' => sanitize_text_field($_POST['endpoint_method'])
        ];

        update_option('wpwps_api_endpoints', $endpoints);
        add_settings_error(
            'wpwps_messages',
            'wpwps_endpoint_added',
            'Endpoint added successfully',
            'updated'
        );
    }
}