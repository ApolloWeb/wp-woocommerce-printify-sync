<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Admin;

/**
 * Class to handle the settings page
 */
class SettingsPage extends AdminPage {
    /**
     * Settings instance
     *
     * @var Settings
     */
    protected $settings;
    
    /**
     * Settings fields
     * 
     * @var array
     */
    protected $fields = [];
    
    /**
     * Settings sections
     * 
     * @var array
     */
    protected $sections = [];
    
    /**
     * Constructor
     *
     * @param Settings $settings
     */
    public function __construct(Settings $settings) {
        $this->settings = $settings;
        $this->page_title = __('Printify Sync Settings', 'wp-woocommerce-printify-sync');
        $this->menu_title = __('Settings', 'wp-woocommerce-printify-sync');
        $this->menu_slug = 'wpwps-settings';
        $this->parent_slug = 'wpwps-dashboard'; // Make it a submenu of our own dashboard
        $this->capability = 'manage_options';
    }

    /**
     * Initialize the settings page
     */
    public function init() {
        parent::init();
        
        // Load fields and sections from settings
        add_action('admin_init', [$this, 'load_settings_data'], 5);
    }
    
    /**
     * Load settings data
     */
    public function load_settings_data() {
        // Get fields and sections from settings
        if (method_exists($this->settings, 'get_sections')) {
            $this->sections = $this->settings->get_sections();
        } else {
            $this->sections = [];
        }
        
        if (method_exists($this->settings, 'get_fields')) {
            $this->fields = $this->settings->get_fields();
        } else {
            $this->fields = [];
        }
    }

    /**
     * Render the page content
     */
    protected function render_content() {
        ?>
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><?php _e('Plugin Settings', 'wp-woocommerce-printify-sync'); ?></h5>
                        </div>
                        <div class="card-body">
                            <form method="post" action="options.php" class="wpwps-settings-form">
                                <?php
                                settings_fields('wpwps_settings');
                                
                                // Safely sort sections if they exist
                                if (is_array($this->sections) && !empty($this->sections)) {
                                    uasort($this->sections, function($a, $b) {
                                        return (isset($a['order']) && isset($b['order'])) 
                                            ? $a['order'] - $b['order'] 
                                            : 0;
                                    });
                                    
                                    foreach ($this->sections as $section_id => $section) {
                                        echo '<div class="wpwps-settings-section mb-4">';
                                        echo '<h3>' . esc_html($section['title']) . '</h3>';
                                        
                                        if (!empty($section['description'])) {
                                            echo '<p class="text-muted">' . esc_html($section['description']) . '</p>';
                                        }
                                        
                                        if (isset($this->fields[$section_id]) && is_array($this->fields[$section_id])) {
                                            echo '<div class="wpwps-settings-fields">';
                                            foreach ($this->fields[$section_id] as $field) {
                                                $this->render_field($field);
                                            }
                                            echo '</div>';
                                        }
                                        
                                        echo '</div>';
                                    }
                                } else {
                                    // If there are no sections or fields, show default message
                                    echo '<div class="alert alert-info">';
                                    _e('No settings fields have been defined yet.', 'wp-woocommerce-printify-sync');
                                    echo '</div>';
                                }
                                
                                submit_button(__('Save Settings', 'wp-woocommerce-printify-sync'), 'primary', 'submit', true, ['class' => 'btn btn-primary']);
                                ?>
                                
                                <!-- Toast notification examples -->
                                <div class="mt-4 p-3 bg-light rounded">
                                    <h6 class="mb-3"><?php _e('Toast Notification Examples', 'wp-woocommerce-printify-sync'); ?></h6>
                                    <button type="button" class="btn btn-success me-2 wpwps-toast-example-success">Success Toast</button>
                                    <button type="button" class="btn btn-danger me-2 wpwps-toast-example-error">Error Toast</button>
                                    <button type="button" class="btn btn-warning me-2 wpwps-toast-example-warning">Warning Toast</button>
                                    <button type="button" class="btn btn-info wpwps-toast-example-info">Info Toast</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render a field
     *
     * @param array $field Field data
     */
    protected function render_field($field) {
        if (!isset($field['id']) || !isset($field['type'])) {
            return;
        }
        
        // Get saved value
        $option_name = 'wpwps_options';
        $options = get_option($option_name, []);
        $value = isset($options[$field['id']]) ? $options[$field['id']] : (isset($field['default']) ? $field['default'] : '');
        
        ?>
        <div class="mb-3">
            <label for="<?php echo esc_attr($field['id']); ?>" class="form-label">
                <?php echo esc_html($field['title']); ?>
            </label>
            
            <?php switch ($field['type']):
                case 'text': ?>
                    <input type="text" 
                        id="<?php echo esc_attr($field['id']); ?>" 
                        name="<?php echo esc_attr($option_name); ?>[<?php echo esc_attr($field['id']); ?>]" 
                        value="<?php echo esc_attr($value); ?>" 
                        class="form-control" />
                    <?php break;
                    
                case 'number': ?>
                    <input type="number" 
                        id="<?php echo esc_attr($field['id']); ?>" 
                        name="<?php echo esc_attr($option_name); ?>[<?php echo esc_attr($field['id']); ?>]" 
                        value="<?php echo esc_attr($value); ?>" 
                        class="form-control" />
                    <?php break;
                    
                case 'checkbox': ?>
                    <div class="form-check">
                        <input type="checkbox" 
                            id="<?php echo esc_attr($field['id']); ?>" 
                            name="<?php echo esc_attr($option_name); ?>[<?php echo esc_attr($field['id']); ?>]" 
                            value="1" 
                            <?php checked(1, $value); ?> 
                            class="form-check-input" />
                        <label class="form-check-label" for="<?php echo esc_attr($field['id']); ?>">
                            <?php echo isset($field['checkbox_label']) ? esc_html($field['checkbox_label']) : ''; ?>
                        </label>
                    </div>
                    <?php break;
                    
                case 'select': ?>
                    <select 
                        id="<?php echo esc_attr($field['id']); ?>" 
                        name="<?php echo esc_attr($option_name); ?>[<?php echo esc_attr($field['id']); ?>]" 
                        class="form-select">
                        <?php if (isset($field['options']) && is_array($field['options'])): ?>
                            <?php foreach ($field['options'] as $option_value => $option_label): ?>
                                <option value="<?php echo esc_attr($option_value); ?>" <?php selected($option_value, $value); ?>>
                                    <?php echo esc_html($option_label); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <?php break;
                    
                case 'textarea': ?>
                    <textarea 
                        id="<?php echo esc_attr($field['id']); ?>" 
                        name="<?php echo esc_attr($option_name); ?>[<?php echo esc_attr($field['id']); ?>]" 
                        class="form-control" 
                        rows="5"><?php echo esc_textarea($value); ?></textarea>
                    <?php break;
                
                default:
                    do_action('wpwps_render_setting_field_' . $field['type'], $field, $value, $option_name);
                    break;
            endswitch; ?>
            
            <?php if (isset($field['description'])): ?>
                <div class="form-text text-muted"><?php echo esc_html($field['description']); ?></div>
            <?php endif; ?>
        </div>
        <?php
    }
}
