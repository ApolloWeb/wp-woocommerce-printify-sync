<?php
/**
 * Forms Component
 *
 * Provides form component rendering for the admin interface.
 *
 * @package ApolloWeb\WPWooCommercePrintifySync\Admin\Theme\ThemeComponents
 * @version 1.0.0
 * @author ApolloWeb
 * @since 1.0.0
 */

namespace ApolloWeb\WPWooCommercePrintifySync\Admin\Theme\ThemeComponents;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Forms class
 */
class Forms {
    /**
     * Initialize the component
     */
    public static function init() {
        // Register form template functions
        add_action('wpwprintifysync_render_form_field', array(__CLASS__, 'render_form_field'), 10, 1);
        add_action('wpwprintifysync_render_form_section', array(__CLASS__, 'render_form_section'), 10, 2);
        
        // Override WP's admin form elements with Bootstrap styles
        add_action('admin_head', array(__CLASS__, 'admin_form_styles'), 100);
    }
    
    /**
     * Render a form field
     *
     * @param array $args Field arguments
     */
    public static function render_form_field($args) {
        // Parse arguments
        $defaults = array(
            'id' => '',
            'name' => '',
            'type' => 'text',
            'value' => '',
            'label' => '',
            'description' => '',
            'placeholder' => '',
            'class' => '',
            'options' => array(),
            'required' => false,
            'disabled' => false,
            'readonly' => false,
            'min' => '',
            'max' => '',
            'step' => '',
            'rows' => 5,
            'cols' => 40,
            'group_class' => '',
        );
        
        $args = wp_parse_args($args, $defaults);
        
        // Ensure ID and name are set
        if (empty($args['id'])) {
            $args['id'] = sanitize_title($args['name']);
        }
        
        // Field wrapper classes
        $group_class = 'mb-3';
        if (!empty($args['group_class'])) {
            $group_class .= ' ' . $args['group_class'];
        }
        
        // Field classes
        $field_class = 'form-control';
        if ($args['type'] === 'checkbox' || $args['type'] === 'radio') {
            $field_class = 'form-check-input';
        } elseif ($args['type'] === 'color') {
            $field_class = 'form-control form-control-color';
        } elseif ($args['type'] === 'file') {
            $field_class = 'form-control-file';
        } elseif ($args['type'] === 'range') {
            $field_class = 'form-range';
        } elseif ($args['type'] === 'select') {
            $field_class = 'form-select';
        }
        
        if (!empty($args['class'])) {
            $field_class .= ' ' . $args['class'];
        }
        
        // Required attributes
        $required = $args['required'] ? ' required' : '';
        $disabled = $args['disabled'] ? ' disabled' : '';
        $readonly = $args['readonly'] ? ' readonly' : '';
        
        // Start field rendering
        echo '<div class="' . esc_attr($group_class) . '">';
        
        // Label
        if (!empty($args['label']) && $args['type'] !== 'checkbox' && $args['type'] !== 'radio') {
            echo '<label for="' . esc_attr($args['id']) . '" class="form-label">' . esc_html($args['label']) . ($args['required'] ? ' <span class="text-danger">*</span>' : '') . '</label>';
        }
        
        // Render field based on type
        switch ($args['type']) {
            case 'checkbox':
                echo '<div class="form-check">';
                echo '<input type="checkbox" class="' . esc_attr($field_class) . '" id="' . esc_attr($args['id']) . '" name="' . esc_attr($args['name']) . '" value="1"' . checked($args['value'], 1, false) . $required . $disabled . $readonly . '>';
                
                if (!empty($args['label'])) {
                    echo '<label class="form-check-label" for="' . esc_attr($args['id']) . '">' . esc_html($args['label']) . '</label>';
                }
                
                echo '</div>';
                break;
                
            case 'radio':
                echo '<div class="form-check">';
                
                if (!empty($args['options'])) {
                    foreach ($args['options'] as $option_value => $option_label) {
                        $option_id = $args['id'] . '_' . sanitize_title($option_value);
                        
                        echo '<div class="form-check">';
                        echo '<input type="radio" class="' . esc_attr($field_class) . '" id="' . esc_attr($option_id) . '" name="' . esc_attr($args['name']) . '" value="' . esc_attr($option_value) . '"' . checked($args['value'], $option_value, false) . $required . $disabled . $readonly . '>';
                        echo '<label class="form-check-label" for="' . esc_attr($option_id) . '">' . esc_html($option_label) . '</label>';
                        echo '</div>';
                    }
                }
                
                echo '</div>';
                break;
                
            case 'select':
                echo '<select class="' . esc_attr($field_class) . '" id="' . esc_attr($args['id']) . '" name="' . esc_attr($args['name']) . '"' . $required . $disabled . '>';
                
                if (!empty($args['options'])) {
                    foreach ($args['options'] as $option_value => $option_label) {
                        echo '<option value="' . esc_attr($option_value) . '"' . selected($args['value'], $option_value, false) . '>' . esc_html($option_label) . '</option>';
                    }
                }
                
                echo '</select>';
                break;
                
            case 'textarea':
                echo '<textarea class="' . esc_attr($field_class) . '" id="' . esc_attr($args['id']) . '" name="' . esc_attr($args['name']) . '" placeholder="' . esc_attr($args['placeholder']) . '" rows="' . esc_attr($args['rows']) . '" cols="' . esc_attr($args['cols']) . '"' . $required . $disabled . $readonly . '>' . esc_textarea($args['value']) . '</textarea>';
                break;
                
            case 'color':
                echo '<input type="color" class="' . esc_attr($field_class) . '" id="' . esc_attr($args['id']) . '" name="' . esc_attr($args['name']) . '" value="' . esc_attr($args['value']) . '"' . $required . $disabled . $readonly . '>';
                break;
                
            case 'range':
                echo '<input type="range" class="' . esc_attr($field_class) . '" id="' . esc_attr($args['id']) . '" name="' . esc_attr($args['name']) . '" value="' . esc_attr($args['value']) . '"';
                
                if (!empty($args['min'])) {
                    echo ' min="' . esc_attr($args['min']) . '"';
                }
                
                if (!empty($args['max'])) {
                    echo ' max="' . esc_attr($args['max']) . '"';
                }
                
                if (!empty($args['step'])) {
                    echo ' step="' . esc_attr($args['step']) . '"';
                }
                
                echo $required . $disabled . $readonly . '>';
                break;
                
            case 'number':
                echo '<input type="number" class="' . esc_attr($field_class) . '" id="' . esc_attr($args['id']) . '" name="' . esc_attr($args['name']) . '" value="' . esc_attr($args['value']) . '" placeholder="' . esc_attr($args['placeholder']) . '"';
                
                if (!empty($args['min'])) {