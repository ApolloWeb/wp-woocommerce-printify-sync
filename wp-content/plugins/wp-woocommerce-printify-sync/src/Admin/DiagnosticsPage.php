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
                            var errorsList = $('.errors-list');
                            var errorMessage = response.data.message || 'Unknown error occurred.';
                            
                            // Add the main error message
                            errorsList.append('<li class="list-group-item list-group-item-danger">' + errorMessage + '</li>');
                            
                            // Add stack trace if available (for admin debugging purposes)
                            if (response.data.trace) {
                                errorsList.append('<li class="list-group-item list-group-item-danger"><details><summary>Technical Details (for debugging)</summary><pre style="white-space: pre-wrap;">' + response.data.trace + '</pre></details></li>');
                            }
                            
                            $('.error-count').text('1');
                            
                            // Log to console for easier debugging
                            console.error('Diagnostics error:', response.data);
                        }
                    },
                    error: function(xhr, status, error) {
                        var errorsList = $('.errors-list');
                        errorsList.append('<li class="list-group-item list-group-item-danger">AJAX request failed: ' + status + ': ' + error + '</li>');
                        
                        if (xhr.responseText) {
                            try {
                                var jsonResponse = JSON.parse(xhr.responseText);
                                if (jsonResponse.data && jsonResponse.data.message) {
                                    errorsList.append('<li class="list-group-item list-group-item-danger">Server message: ' + jsonResponse.data.message + '</li>');
                                }
                            } catch(e) {
                                errorsList.append('<li class="list-group-item list-group-item-danger"><details><summary>Server Response</summary><pre>' + xhr.responseText + '</pre></details></li>');
                            }
                        }
                        
                        $('.error-count').text(errorsList.find('li').length);
                        console.error('AJAX Error:', xhr.responseText);
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
            // Enable error reporting for debugging
            $original_error_level = error_reporting();
            error_reporting(E_ALL);
            $original_display_errors = ini_get('display_errors');
            ini_set('display_errors', 1);
            
            // Log the beginning of diagnostics
            $this->logger->info('Starting diagnostics run');
            
            // Basic system info for logging
            $this->logger->info('PHP Version: ' . PHP_VERSION);
            $this->logger->info('WPWPS_PLUGIN_DIR: ' . (defined('WPWPS_PLUGIN_DIR') ? WPWPS_PLUGIN_DIR : 'Not defined'));
            
            // Validate that the Diagnostics class exists
            if (!class_exists('ApolloWeb\WPWooCommercePrintifySync\Utilities\Diagnostics')) {
                throw new \Exception('Diagnostics class not found. Check autoloader and namespace.');
            }
            
            // Create diagnostics instance
            $diagnostics = new Diagnostics($this->logger);
            
            // Run diagnostics with additional error handlers
            set_error_handler(function($errno, $errstr, $errfile, $errline) {
                $this->logger->error("PHP Error ($errno): $errstr in $errfile on line $errline");
                // Don't throw an exception for warnings/notices during diagnostics
                if ($errno === E_ERROR || $errno === E_PARSE || $errno === E_CORE_ERROR || 
                    $errno === E_COMPILE_ERROR || $errno === E_USER_ERROR) {
                    throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
                }
                return true; // Return true to continue execution for non-fatal errors
            });
            
            // Run the diagnostics
            $results = $diagnostics->runAll();
            
            // Restore original error handler
            restore_error_handler();
            
            // Restore original error settings
            error_reporting($original_error_level);
            ini_set('display_errors', $original_display_errors);
            
            // Log success
            $this->logger->info('Diagnostics completed successfully', [
                'error_count' => count($results['errors']),
                'warning_count' => count($results['warnings']),
                'notice_count' => count($results['notices']),
            ]);
            
            wp_send_json_success($results);
        } catch (\Throwable $e) {
            // Catch all errors (including PHP 7 Error objects)
            $error_message = $e->getMessage();
            $error_trace = $e->getTraceAsString();
            
            // Log detailed error
            $this->logger->error('Diagnostics failed: ' . $error_message);
            $this->logger->debug('Error trace: ' . $error_trace);
            
            // Send a more detailed error message back to the client
            wp_send_json_error([
                'message' => 'Diagnostics error: ' . $error_message,
                'trace' => $error_trace,
            ]);
        }
    }
}
