<?php
/**
 * Diagnostics admin page.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

use ApolloWeb\WPWooCommercePrintifySync\Services\Logger;
use ApolloWeb\WPWooCommercePrintifySync\Utilities\Diagnostics;

/**
 * Class DiagnosticsPage
 */
class DiagnosticsPage {
    /**
     * Logger instance.
     *
     * @var Logger
     */
    private $logger;

    /**
     * Constructor.
     *
     * @param Logger $logger Logger instance.
     */
    public function __construct(Logger $logger) {
        $this->logger = $logger;
    }

    /**
     * Initialize the diagnostics page.
     */
    public function init() {
        // Add as a top-level menu item instead of submenu for testing
        add_menu_page(
            __('Diagnostics', 'wp-woocommerce-printify-sync'),   // Page title
            __('Printify Diagnostics', 'wp-woocommerce-printify-sync'),   // Menu title
            'manage_options',               // Capability - Using a common capability for testing
            'wpwps-diagnostics',            // Menu slug
            [$this, 'renderPage'],          // Callback
            'dashicons-chart-area',         // Icon
            100                             // Position
        );
        
        // We'll keep the submenu registration as well, but the standalone menu will help for testing
        add_submenu_page(
            'wpwps-dashboard',              // Parent slug
            __('Diagnostics', 'wp-woocommerce-printify-sync'),   // Page title
            __('Diagnostics', 'wp-woocommerce-printify-sync'),   // Menu title
            'manage_options',               // Capability - changed to common WordPress capability
            'wpwps-diagnostics',            // Menu slug
            [$this, 'renderPage']           // Callback
        );

        // Register AJAX handlers
        add_action('wp_ajax_wpwps_run_diagnostics', [$this, 'runDiagnostics']);
        
        // Add debug information to the page to help troubleshoot
        add_action('admin_notices', [$this, 'debugNotice']);
    }
    
    /**
     * Display debug information for troubleshooting.
     */
    public function debugNotice() {
        global $pagenow, $plugin_page;
        
        // Only show on our diagnostics page or WP admin
        if (!is_admin() || ($pagenow !== 'admin.php' && $plugin_page !== 'wpwps-diagnostics')) {
            return;
        }
        
        echo '<div class="notice notice-info is-dismissible">';
        echo '<p><strong>Debug Info:</strong></p>';
        echo '<p>Current page: ' . esc_html($pagenow) . '</p>';
        echo '<p>Plugin page: ' . esc_html($plugin_page) . '</p>';
        echo '<p>Current user can manage_options: ' . (current_user_can('manage_options') ? 'Yes' : 'No') . '</p>';
        echo '<p>Access this page at: <a href="' . esc_url(admin_url('admin.php?page=wpwps-diagnostics')) . '">Diagnostics Page</a></p>';
        echo '</div>';
    }

    /**
     * Render the diagnostics page.
     */
    public function renderPage() {
        // Check for permissions here as well
        if (!current_user_can('manage_options')) {
            wp_die(__('Sorry, you do not have sufficient permissions to access this page.', 'wp-woocommerce-printify-sync'));
        }
        ?>
        <div class="wrap wpwps-admin-wrap">
            <h1 class="wp-heading-inline">
                <i class="fas fa-stethoscope"></i> <?php esc_html_e('Printify Sync - Diagnostics', 'wp-woocommerce-printify-sync'); ?>
            </h1>
            
            <hr class="wp-header-end">
            
            <div class="wpwps-card">
                <div class="card-body">
                    <h5 class="card-title">
                        <?php esc_html_e('Code Diagnostics', 'wp-woocommerce-printify-sync'); ?>
                    </h5>
                    <p class="card-text">
                        <?php esc_html_e('Run diagnostics to check for potential issues in the plugin code.', 'wp-woocommerce-printify-sync'); ?>
                    </p>
                    
                    <button id="run-diagnostics" class="btn btn-primary">
                        <i class="fas fa-play"></i> <?php esc_html_e('Run Diagnostics', 'wp-woocommerce-printify-sync'); ?>
                    </button>
                    
                    <div id="diagnostics-results" class="mt-4" style="display: none;">
                        <div class="results-spinner text-center mb-3">
                            <i class="fas fa-circle-notch fa-spin fa-2x"></i>
                            <p><?php esc_html_e('Running diagnostics...', 'wp-woocommerce-printify-sync'); ?></p>
                        </div>
                        
                        <div class="results-content" style="display: none;">
                            <h6><?php esc_html_e('Results:', 'wp-woocommerce-printify-sync'); ?></h6>
                            
                            <div class="errors-section mb-3">
                                <h6 class="text-danger">
                                    <i class="fas fa-times-circle"></i> <?php esc_html_e('Errors', 'wp-woocommerce-printify-sync'); ?>
                                    (<span class="error-count">0</span>)
                                </h6>
                                <ul class="errors-list list-group"></ul>
                            </div>
                            
                            <div class="warnings-section mb-3">
                                <h6 class="text-warning">
                                    <i class="fas fa-exclamation-triangle"></i> <?php esc_html_e('Warnings', 'wp-woocommerce-printify-sync'); ?>
                                    (<span class="warning-count">0</span>)
                                </h6>
                                <ul class="warnings-list list-group"></ul>
                            </div>
                            
                            <div class="notices-section mb-3">
                                <h6 class="text-info">
                                    <i class="fas fa-info-circle"></i> <?php esc_html_e('Notices', 'wp-woocommerce-printify-sync'); ?>
                                    (<span class="notice-count">0</span>)
                                </h6>
                                <ul class="notices-list list-group"></ul>
                            </div>
                            
                            <div class="success-message alert alert-success" style="display: none;">
                                <i class="fas fa-check-circle"></i> <?php esc_html_e('No issues found!', 'wp-woocommerce-printify-sync'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#run-diagnostics').on('click', function() {
                var button = $(this);
                var resultsSection = $('#diagnostics-results');
                var spinner = $('.results-spinner');
                var content = $('.results-content');
                
                // Reset previous results
                $('.errors-list, .warnings-list, .notices-list').empty();
                $('.error-count').text('0');
                $('.warning-count').text('0');
                $('.notice-count').text('0');
                $('.success-message').hide();
                
                // Show spinner
                button.prop('disabled', true);
                resultsSection.show();
                spinner.show();
                content.hide();
                
                // Make AJAX request
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'wpwps_run_diagnostics',
                        nonce: '<?php echo wp_create_nonce('wpwps_diagnostics_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            // Update counts
                            $('.error-count').text(response.data.errors.length);
                            $('.warning-count').text(response.data.warnings.length);
                            $('.notice-count').text(response.data.notices.length);
                            
                            // Populate lists
                            var errorsList = $('.errors-list');
                            var warningsList = $('.warnings-list');
                            var noticesList = $('.notices-list');
                            
                            if (response.data.errors.length > 0) {
                                $.each(response.data.errors, function(i, error) {
                                    errorsList.append('<li class="list-group-item list-group-item-danger">' + error + '</li>');
                                });
                            }
                            
                            if (response.data.warnings.length > 0) {
                                $.each(response.data.warnings, function(i, warning) {
                                    warningsList.append('<li class="list-group-item list-group-item-warning">' + warning + '</li>');
                                });
                            }
                            
                            if (response.data.notices.length > 0) {
                                $.each(response.data.notices, function(i, notice) {
                                    noticesList.append('<li class="list-group-item list-group-item-info">' + notice + '</li>');
                                });
                            }
                            
                            // Show success message if no issues found
                            if (response.data.errors.length === 0 && 
                                response.data.warnings.length === 0 && 
                                response.data.notices.length === 0) {
                                $('.success-message').show();
                            }
                        } else {
                            errorsList.append('<li class="list-group-item list-group-item-danger">' + response.data.message + '</li>');
                            $('.error-count').text('1');
                        }
                    },
                    error: function() {
                        var errorsList = $('.errors-list');
                        errorsList.append('<li class="list-group-item list-group-item-danger">Failed to run diagnostics. Please try again.</li>');
                        $('.error-count').text('1');
                    },
                    complete: function() {
                        // Hide spinner and show content
                        spinner.hide();
                        content.show();
                        button.prop('disabled', false);
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Run diagnostics via AJAX.
     */
    public function runDiagnostics() {
        check_ajax_referer('wpwps_diagnostics_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => __('You do not have permission to do this.', 'wp-woocommerce-printify-sync'),
            ]);
            return;
        }
        
        try {
            // Run diagnostics
            $diagnostics = new Diagnostics($this->logger);
            $results = $diagnostics->runAll();
            
            wp_send_json_success($results);
        } catch (\Exception $e) {
            $this->logger->error('Error running diagnostics: ' . $e->getMessage());
            
            wp_send_json_error([
                'message' => $e->getMessage(),
            ]);
        }
    }
}
