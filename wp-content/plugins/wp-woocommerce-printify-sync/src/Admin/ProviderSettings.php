<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

class ProviderSettings
{
    public function init()
    {
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function register_settings()
    {
        register_setting('wpwps_provider_settings', 'wpwps_provider_markups');
        
        add_settings_section(
            'wpwps_markup_settings',
            'Provider Markup Settings',
            [$this, 'render_section'],
            'wpwps_settings'
        );
        
        add_settings_field(
            'provider_markups',
            'Provider Markups',
            [$this, 'render_provider_markups'],
            'wpwps_settings',
            'wpwps_markup_settings'
        );
    }

    public function render_provider_markups()
    {
        $markups = get_option('wpwps_provider_markups', []);
        ?>
        <table class="widefat">
            <thead>
                <tr>
                    <th>Provider</th>
                    <th>Default Markup %</th>
                    <th>Product Types</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>SPOD</td>
                    <td>
                        <input type="number" 
                               name="wpwps_provider_markups[providers][SPOD]" 
                               value="<?php echo esc_attr($markups['providers']['SPOD'] ?? 40); ?>" />
                    </td>
                    <td>
                        <button type="button" class="button add-product-type">Add Product Type</button>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php
    }
}
