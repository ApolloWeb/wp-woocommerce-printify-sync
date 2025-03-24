<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Template;

/**
 * Utility class for debugging template rendering issues
 */
class TemplateDebugger {
    
    /**
     * Add detailed debug information to the page
     * 
     * @param array $data The data passed to the template
     * @return string HTML debug output
     */
    public static function getDebugInfo(array $data): string {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return '';
        }
        
        ob_start();
        ?>
        <div class="wpwps-template-debug" style="background:#f8f9f9; padding:15px; margin:20px 0; border-left:4px solid #dc3545; font-family:monospace;">
            <h3>🔍 Template Debug Information</h3>
            <p><strong>Time:</strong> <?php echo esc_html(current_time('mysql')); ?></p>
            <p><strong>Request:</strong> <?php echo esc_html($_SERVER['REQUEST_URI'] ?? 'unknown'); ?></p>
            
            <h4>Template Data:</h4>
            <ul>
                <?php foreach ($data as $key => $value): ?>
                    <?php if (is_scalar($value) || is_null($value)): ?>
                        <li><strong><?php echo esc_html($key); ?>:</strong> <?php echo esc_html(var_export($value, true)); ?></li>
                    <?php else: ?>
                        <li><strong><?php echo esc_html($key); ?>:</strong> [<?php echo esc_html(gettype($value)); ?>]</li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
            
            <p><button onclick="jQuery('.wpwps-debug-data').toggle();" class="button">Show/Hide Full Data</button></p>
            
            <div class="wpwps-debug-data" style="display:none; max-height:300px; overflow:auto; background:#f1f1f1; padding:10px; font-size:12px;">
                <pre><?php echo esc_html(print_r($data, true)); ?></pre>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Dump variables to the error log
     * 
     * @param mixed $var The variable to debug
     * @param string $label Optional label for the log entry
     * @return void
     */
    public static function logDebug($var, string $label = 'Debug'): void {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }
        
        error_log("WPWPS {$label}: " . print_r($var, true));
    }
}
