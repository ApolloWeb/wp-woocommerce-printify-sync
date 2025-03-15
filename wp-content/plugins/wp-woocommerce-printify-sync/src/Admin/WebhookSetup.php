<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

class WebhookSetup
{
    private string $currentTime = '2025-03-15 19:51:27';
    private string $currentUser = 'ApolloWeb';

    public function __construct()
    {
        add_action('admin_menu', [$this, 'addMenuPage']);
        add_action('admin_init', [$this, 'registerSettings']);
    }

    public function addMenuPage(): void
    {
        add_submenu_page(
            'tools.php',
            'Printify Webhook Setup',
            'Printify Webhook',
            'manage_options',
            'printify-webhook',
            [$this, 'renderPage']
        );
    }

    public function registerSettings(): void
    {
        register_setting('wpwps_webhook', 'wpwps_webhook_secret');
    }

    public function renderPage(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $webhookUrl = rest_url('wpwps/v1/webhook');
        $webhookSecret = get_option('wpwps_webhook_secret');

        if (empty($webhookSecret)) {
            $webhookSecret = wp_generate_password(32, false);
            update_option('wpwps_webhook_secret', $webhookSecret);
        }

        ?>
        <div class="wrap">
            <h1>Printify Webhook Setup</h1>
            
            <div class="card">
                <h2>Webhook Configuration</h2>
                <p>Use these details to configure your webhook in Printify:</p>

                <table class="form-table">
                    <tr>
                        <th>Webhook URL:</th>
                        <td>
                            <code><?php echo esc_html($webhookUrl); ?></code>
                            <button class="button" onclick="navigator.clipboard.writeText('<?php echo esc_js($webhookUrl); ?>')">
                                Copy
                            </button>
                        </td>
                    </tr>
                    <tr>
                        <th>Secret Key:</th>
                        <td>
                            <code><?php echo esc_html($webhookSecret); ?></code>
                            <button class="button" onclick="navigator.clipboard.writeText('<?php echo esc_js($webhookSecret); ?>')">
                                Copy
                            </button>
                        </td>
                    </tr>
                </table>

                <div class="webhook-setup-steps">
                    <h3>Setup Instructions</h3>
                    <ol>
                        <li>Go to your Printify dashboard</li>
                        <li>Navigate to Settings → Webhooks</li>
                        <li>Click "Add New Webhook"</li>
                        <li>Enter the Webhook URL shown above</li>
                        <li>Enter the Secret Key shown above</li>
                        <li>Select the following events:
                            <ul>
                                <li>product.created</li>
                                <li>product.updated</li>
                            </ul>
                        </li>
                        <li>Click "Save" to activate the webhook</li>
                    </ol>
                </div>

                <div class="webhook-test">
                    <h3>Test Webhook</h3>
                    <p>Click the button below to test the webhook connection:</p>
                    <button id="test-webhook" class="button button-primary">
                        Test Webhook
                    </button>
                    <div id="test-result" style="display: none;"></div>
                </div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#test-webhook').on('click', function() {
                const button = $(this);
                const result = $('#test-result');
                
                button.prop('disabled', true);
                result.html('Testing webhook...').show();

                $.get('<?php echo esc_js($webhookUrl); ?>')
                    .done(function(response) {
                        result.html('<div class="notice notice-success"><p>Webhook test successful!</p></div>');
                    })
                    .fail(function(xhr) {
                        result.html('<div class="notice notice-error"><p>Webhook test failed: ' + xhr.responseText + '</p></div>');
                    })
                    .always(function() {
                        button.prop('disabled', false);
                    });
            });
        });
        </script>
        <?php
    }
}